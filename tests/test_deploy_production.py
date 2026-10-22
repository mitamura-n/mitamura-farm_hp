"""Safety boundaries for the routine deployer; no server writes or network calls."""
import importlib.util
from pathlib import Path
import tempfile
import tarfile
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('deployer', Path(__file__).resolve().parents[1] / 'scripts/deploy-production.py')
deploy = importlib.util.module_from_spec(spec)
spec.loader.exec_module(deploy)


class DeploymentGuards(unittest.TestCase):
    def test_allowed_assets(self):
        for path in ('style.css', 'app.js', 'images/new/rice.webp', 'favicon.ico', 'robots.txt'):
            self.assertTrue(deploy.static_asset(path), path)

    def test_unsafe_assets(self):
        for path in ('../.env', '/tmp/a.js', 'images/key.php', '.env', 'images/.private/a.png',
                     'images/a.png/../../a.php', 'directory/app.js', 'images/a b.jpg'):
            self.assertFalse(deploy.static_asset(path), path)

    def test_schema_dependencies_and_public_deletions_stop(self):
        for record in (('M', 'composer.lock'), ('A', 'database/migrations/new.php'),
                       ('M', 'config/mail.php'), ('D', 'public/app.js'),
                       ('M', 'public/.htaccess'), ('M', 'public/index.php')):
            with self.assertRaises(deploy.DeployError):
                deploy.check_changes([record])

    def test_routine_blade_and_asset_changes(self):
        deploy.check_changes([('M', 'resources/views/farm.blade.php'), ('A', 'public/images/rice.jpg')])

    def test_revision_rejects_shell_and_paths(self):
        for revision in ('HEAD', '../main', 'abcd123;whoami', 'a' * 41):
            with self.assertRaises(deploy.DeployError):
                deploy.validate_revision(revision)

    def test_dirty_tree_stops_before_network(self):
        with patch.object(deploy, 'git', return_value=' M resources/views/farm.blade.php') as git:
            with self.assertRaises(deploy.DeployError):
                deploy.local_check()
            git.assert_called_once_with('status', '--porcelain')

    def test_unpushed_commit_stops(self):
        with patch.object(deploy, 'git', side_effect=['', 'main', 'a' * 40, 'b' * 40 + '\trefs/heads/main']):
            with self.assertRaises(deploy.DeployError):
                deploy.local_check()

    def test_check_mode_cannot_upload_or_publish(self):
        with patch('sys.argv', ['deploy-production.py']), patch.object(deploy, 'ssh_options', return_value=[]), \
                patch.object(deploy, 'local_check', return_value='a' * 40), \
                patch.object(deploy, 'production_plan', return_value=('b' * 7, [], False)), \
                patch.object(deploy, 'tests') as tests, patch.object(deploy, 'remote') as remote, \
                patch.object(deploy, 'archive') as archive:
            deploy.main()
            tests.assert_not_called()
            archive.assert_not_called()
            remote.assert_not_called()

    def test_archive_uses_git_contents_and_empty_manifest(self):
        # This archives the local repository only. It does not contact a remote server.
        with tempfile.TemporaryDirectory() as directory:
            packed = deploy.archive(deploy.git('rev-parse', 'HEAD'), [], Path(directory))
            with tarfile.open(packed) as bundle:
                self.assertEqual(bundle.extractfile('deployment/production/assets.list').read(), b'')
                self.assertNotIn('.env', bundle.getnames())


if __name__ == '__main__':
    unittest.main()
