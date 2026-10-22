#!/usr/bin/env python3
"""Routine production updates. Default: read-only checks. Standard library only."""
import argparse
import gzip
import hashlib
import io
from pathlib import Path, PurePosixPath
import re
import shlex
import shutil
import subprocess
import sys
import tarfile
import tempfile
import urllib.request

REPO = Path(__file__).resolve().parents[1]
BASE = '/home/mitamurafarm/apps/mitamura-production'
HOST = 'mitamurafarm@sv17015.xserver.jp'
URL = 'https://mitamurafarm.com/'
IMAGE_SUFFIXES = {'.png', '.jpg', '.jpeg', '.webp', '.avif', '.gif', '.svg', '.ico'}


class DeployError(RuntimeError):
    pass


def run(args, *, data=None):
    result = subprocess.run(args, cwd=REPO, input=data, stdout=subprocess.PIPE,
                            stderr=subprocess.PIPE)
    if result.returncode:
        # Do not print PHP traces or remote logs which could contain private data.
        with tempfile.NamedTemporaryFile(prefix='mitamura-update-error-', suffix='.log', delete=False) as log:
            log.write(result.stdout + b'\n' + result.stderr)
            log_path = log.name
        raise DeployError(f'{Path(str(args[0])).name} failed (exit {result.returncode}). '
                          f'Publishing stopped. Local error log: {log_path}')
    return result.stdout


def git(*args):
    return run(['git', *args]).decode('utf-8').strip()


def validate_revision(value):
    if not re.fullmatch(r'[a-f0-9]{7,40}', value):
        raise DeployError('Invalid release identifier.')
    return value


def static_asset(path):
    relative = PurePosixPath(path)
    if (not path or not re.fullmatch(r'[A-Za-z0-9_./-]+', path)
            or '..' in path or relative.is_absolute()
            or any(part.startswith('.') for part in relative.parts)):
        return False
    if relative.parts[0] == 'images':
        return relative.suffix.lower() in IMAGE_SUFFIXES
    return len(relative.parts) == 1 and (
        relative.suffix in {'.css', '.js'} or path in {'favicon.ico', 'robots.txt'})


def check_changes(changes):
    """Routine updates cannot replace infrastructure, dependencies or DB schema."""
    for status, path in changes:
        if (path in {'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
                     'public/.htaccess', 'public/index.php', 'vite.config.js'}
                or path.startswith(('database/', 'bootstrap/', 'config/'))):
            raise DeployError(f'Individual deployment review required: {path}')
        if path.startswith('public/') and (status != 'M' and status != 'A'
                                          or not static_asset(path[7:])):
            raise DeployError(f'Unsupported public asset change: {path}')


def local_check():
    if git('status', '--porcelain'):
        raise DeployError('Uncommitted changes exist. Commit your own changes first.')
    if git('branch', '--show-current') != 'main':
        raise DeployError('Switch to main before a routine production update.')
    revision = validate_revision(git('rev-parse', 'HEAD'))
    origin = git('ls-remote', '--exit-code', 'origin', 'refs/heads/main').split()[0]
    if origin != revision:
        raise DeployError('Local HEAD and GitHub main differ. Review and push/pull first.')
    return revision


def ssh_options(keydir):
    for name in ('id_rsa', 'known_hosts'):
        if not (keydir / name).is_file():
            raise DeployError(f'Required SSH file is missing: {name}')
    return ['-i', str(keydir / 'id_rsa'), '-o', 'BatchMode=yes', '-o',
            'IdentitiesOnly=yes', '-o', 'StrictHostKeyChecking=yes', '-o',
            'UserKnownHostsFile=' + str(keydir / 'known_hosts'),
            '-o', 'ConnectTimeout=15']


def remote(opts, mode, revision, expected):
    validate_revision(revision)
    validate_revision(expected)
    helper = (REPO / 'scripts/deploy-production.sh').read_text(encoding='utf-8')
    command = 'bash -c ' + shlex.quote(helper) + ' -- ' + ' '.join(
        shlex.quote(arg) for arg in (mode, revision, expected))
    data = ((REPO / 'scripts/production-health.php').read_bytes()
            if mode == 'check' else None)
    return run(['ssh', *opts, '-p', '10022', HOST, command], data=data).decode('utf-8')


def production_plan(opts, revision):
    current = run(['ssh', *opts, '-p', '10022', HOST,
                   f'readlink {BASE}/current']).decode().strip()
    if not current.startswith(BASE + '/releases/'):
        raise DeployError('Unexpected current release path.')
    previous = validate_revision(current[len(BASE + '/releases/'):])
    old_commit = git('rev-parse', '--verify', previous + '^{commit}')
    if git('merge-base', '--is-ancestor', old_commit, revision):
        raise DeployError('Unexpected ancestry result.')
    records = git('diff', '--no-renames', '--name-status', old_commit, revision)
    changes = [line.split('\t', 1) for line in records.splitlines()]
    check_changes(changes)
    files = git('ls-tree', '-r', 'HEAD', 'public').splitlines()
    changed_assets = {path[7:] for status, path in changes if path.startswith('public/')}
    assets = []
    for record in files:
        metadata, path = record.split('\t', 1)
        if path in {'public/index.php', 'public/.htaccess'}:
            continue
        if not metadata.startswith('100644 blob ') and not metadata.startswith('100755 blob '):
            raise DeployError('Public symlinks are not supported.')
        if not static_asset(path[7:]):
            raise DeployError(f'Unsupported tracked public file: {path}')
        if path[7:] in changed_assets:
            assets.append(path[7:])
    info = remote(opts, 'check', revision, previous)
    print(f'GitHub/main: {revision[:12]}\nProduction: {previous}')
    print(info.strip())
    print(f'Changed files: {len(changes)}; static files: {len(assets)}')
    return previous, assets, old_commit == revision


def tests():
    php = REPO / '../../.tools/php84/php.exe'
    if not php.is_file():
        raise DeployError('Local PHP is missing: ../../.tools/php84/php.exe')
    node = shutil.which('node')
    if not node:
        raise DeployError('Node.js is required for JavaScript tests.')
    print('Running PHP and JavaScript tests...', flush=True)
    run([sys.executable, '-B', '-m', 'unittest', 'discover', '-s', 'tests', '-p', 'test_deploy_production.py'])
    run([str(php.resolve()), 'artisan', 'test'])
    js = sorted(str(path.relative_to(REPO)) for path in (REPO / 'tests').glob('*.test.cjs'))
    if not js:
        raise DeployError('JavaScript tests were not found.')
    run([node, '--test', *js])
    print('Tests passed.')


def archive(revision, assets, folder):
    raw = folder / 'source.tar'
    run(['git', 'archive', '--format=tar', '--output=' + str(raw), revision])
    manifest = (''.join(asset + '\n' for asset in assets)).encode()
    with tarfile.open(raw, 'a') as bundle:
        entry = tarfile.TarInfo('deployment/production/assets.list')
        entry.size = len(manifest)
        entry.mode = 0o600
        bundle.addfile(entry, io.BytesIO(manifest))
    packed = folder / ('source-' + revision + '.tar.gz')
    with raw.open('rb') as source, gzip.open(packed, 'wb') as target:
        shutil.copyfileobj(source, target)
    return packed


def fetch(path):
    request = urllib.request.Request(URL + path, headers={'Cache-Control': 'no-cache'})
    with urllib.request.urlopen(request, timeout=30) as response:
        if response.status != 200 or response.geturl() != URL + path:
            raise DeployError(f'Unexpected HTTP response: /{path}')
        return response.read()


def verify(revision, assets):
    for path in ('', 'shipping', 'privacy', 'tokushoho'):
        body = fetch(path).decode('utf-8')
        if '<html' not in body or 'mitamurafarm.com' not in body:
            raise DeployError(f'Public page check failed: /{path}')
    admin = fetch('admin/login')
    if b'id="analytics-config"' in admin:
        raise DeployError('Analytics unexpectedly appeared on the admin login.')
    for asset in assets:
        expected = run(['git', 'show', f'{revision}:public/{asset}'])
        if hashlib.sha256(fetch(asset)).digest() != hashlib.sha256(expected).digest():
            raise DeployError(f'Public file checksum mismatch: {asset}')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    action = parser.add_mutually_exclusive_group()
    action.add_argument('--check', action='store_true', help='Read-only preflight (default)')
    action.add_argument('--deploy', action='store_true', help='Test, back up and publish after confirmation')
    action.add_argument('--rollback', metavar='FULL_COMMIT', help='Restore a routine update backup')
    parser.add_argument('--key-directory', type=Path,
                        default=REPO / '../../.local-secrets/xserver-preview')
    args = parser.parse_args()
    opts = ssh_options(args.key_directory.resolve())
    if args.rollback:
        revision = validate_revision(args.rollback)
        if len(revision) != 40:
            raise DeployError('Rollback requires the full 40-character commit from the success message.')
        if input(f'Type rollback {revision[:12]} to restore the previous release: ').strip() != 'rollback ' + revision[:12]:
            raise DeployError('Cancelled; nothing changed.')
        print(remote(opts, 'rollback', revision, revision))
        return
    revision = local_check()
    previous, assets, already_live = production_plan(opts, revision)
    if not args.deploy:
        print('Read-only checks passed. No production files or settings were changed.')
        return
    if already_live:
        print('This commit is already live; no update needed.')
        return
    tests()
    if local_check() != revision:
        raise DeployError('Source changed during testing. Start again.')
    if input(f'Type publish {revision[:12]} to update production: ').strip() != 'publish ' + revision[:12]:
        raise DeployError('Cancelled; nothing changed.')
    with tempfile.TemporaryDirectory(prefix='mitamura-deploy-') as directory:
        packed = archive(revision, assets, Path(directory))
        run(['scp', *opts, '-P', '10022', str(packed), HOST + ':' + BASE + '/' + packed.name])
        print('Backing up and publishing. Keep this window open...', flush=True)
        print(remote(opts, 'deploy', revision, previous).strip())
    try:
        verify(revision, assets)
    except Exception:
        print('Public checks failed. Attempting rollback...', flush=True)
        print(remote(opts, 'rollback', revision, revision).strip())
        raise DeployError('Public verification failed. Previous release restored; investigate before retrying.')
    print(f'SUCCESS: {URL}\nPublished commit: {revision}\nPrevious release: {previous}')
    print('DB/configuration unchanged. No test inquiry or mail was sent.')


if __name__ == '__main__':
    try:
        main()
    except (DeployError, OSError, ValueError, KeyboardInterrupt) as error:
        print(f'STOP: {error}', file=sys.stderr)
        sys.exit(1)
