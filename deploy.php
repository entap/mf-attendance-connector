<?php

namespace Deployer;

require 'recipe/laravel.php';

set('ssh_type', 'native');
set('ssh_multiplexing', true);
set('writable_mode', 'chmod');
set('writable_chmod_mode', "0755");

// Project name
set('application', 'mf-attendance-connector');

// Project repository
set('repository', 'https://github.com/entap/mf-attendance-connector.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys
add('shared_files', ['.env']);
add('shared_dirs', ['storage']);

// Writable dirs by web server
set('writable_dirs', ['bootstrap/cache', 'storage']);

// Clear paths by web server
set('clear_paths', []);

// Hosts

host('entap.city')
    ->user('mf-attendance-connector')
    ->set('deploy_path', '~/{{application}}');

// Tasks

task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'artisan:cache:clear',
    //'artisan:optimize',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
]);

task('build', function () {
    run('cd {{release_path}} && build');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');
