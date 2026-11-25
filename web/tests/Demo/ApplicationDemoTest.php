<?php

use Symfony\Component\Process\Process;

test('the application demonstrates itself in a real browser', function () {
    $base = dirname(__DIR__, 2);
    $port = getenv('DEMO_APP_PORT') ?: '8123';
    $appUrl = 'http://127.0.0.1:'.$port;
    $stubPort = getenv('DEMO_STUB_PORT') ?: '8999';
    $database = $base.'/database/demo.sqlite';
    $hotFile = $base.'/public/hot';
    $hotBackup = $base.'/public/hot.demo-backup';

    if (! file_exists($base.'/public/build/manifest.json')) {
        throw new RuntimeException('Vite assets are missing. Run `npm run build` in web/ before the demo.');
    }

    touch($database);

    // A leftover Vite "hot" file points the app at a dev server that is not
    // running, which leaves the demo unstyled. Use the built assets instead.
    $hotMoved = false;

    if (file_exists($hotFile)) {
        if (file_exists($hotBackup)) {
            unlink($hotBackup);
        }
        rename($hotFile, $hotBackup);
        $hotMoved = true;
    }

    try {
        // The server gets its own env: phpunit.xml's APP_ENV=testing and
        // SESSION_DRIVER=array would leak in and break a real browser session.
        $env = [
            'APP_NAME' => 'ATLAS',
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'false',
            'APP_URL' => $appUrl,
            'APP_MAINTENANCE_DRIVER' => 'file',
            'BCRYPT_ROUNDS' => '4',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $database,
            'DB_URL' => '',
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
            'BROADCAST_CONNECTION' => 'null',
            'LOG_CHANNEL' => 'stderr',
            'GENERATOR_SERVICE_URL' => 'http://127.0.0.1:'.$stubPort,
            'GENERATOR_SERVICE_KEY' => 'demo',
            'GENERATOR_SERVICE_TIMEOUT' => '30',
            'GENERATOR_SCENARIO_TIMEOUT' => '10',
        ];

        $migrate = new Process([PHP_BINARY, 'artisan', 'migrate:fresh', '--force', '--no-interaction'], $base, $env);
        $migrate->setTimeout(120);
        $migrate->run();

        if (! $migrate->isSuccessful()) {
            throw new RuntimeException('Demo database migration failed: '.$migrate->getErrorOutput().$migrate->getOutput());
        }

        $server = new Process(
            [PHP_BINARY, 'artisan', 'serve', '--host=127.0.0.1', '--port='.$port, '--no-reload'],
            $base,
            $env,
        );
        $server->setTimeout(null);
        $server->start();

        try {
            $started = false;

            for ($attempt = 0; $attempt < 100; $attempt++) {
                // cURL returns false on a refused connection without raising a
                // PHP warning, which PHPUnit would otherwise report as a WARN.
                $probe = curl_init($appUrl.'/up');
                curl_setopt_array($probe, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 1,
                    CURLOPT_TIMEOUT => 1,
                ]);
                $body = curl_exec($probe);
                $status = curl_getinfo($probe, CURLINFO_RESPONSE_CODE);

                if ($body !== false && $status === 200) {
                    $started = true;
                    break;
                }

                usleep(200_000);
            }

            if (! $started) {
                throw new RuntimeException('Demo server did not start on '.$appUrl.'. '.$server->getErrorOutput());
            }

            $demo = new Process(
                // Pin Selenium: 4.53 makes the login submit flaky on this setup.
                ['uv', 'run', '--with', 'selenium==4.52.0', 'python', $base.'/tests/Demo/demo_app.py'],
                $base,
                $env + [
                    'DEMO_APP_URL' => $appUrl,
                    'DEMO_STUB_PORT' => $stubPort,
                    // Ignore any activated Python venv; use uv's own Selenium.
                    'VIRTUAL_ENV' => false,
                ],
            );
            $demo->setTimeout(240);
            $demo->run(function (string $type, string $buffer): void {
                echo $buffer;
            });

            if (! $demo->isSuccessful()) {
                throw new RuntimeException(
                    'Browser demo failed with exit code '.$demo->getExitCode().'.'.PHP_EOL
                    .$demo->getOutput().PHP_EOL.$demo->getErrorOutput()
                );
            }

            expect($demo->getOutput())->toContain('[DEMO] PASSED');
        } finally {
            stopDemoServer($server);
        }
    } finally {
        if ($hotMoved) {
            // Vite may have recreated public/hot while the demo ran.
            if (file_exists($hotFile)) {
                if (file_exists($hotBackup)) {
                    unlink($hotBackup);
                }
            } else {
                rename($hotBackup, $hotFile);
            }
        }
    }
});

function stopDemoServer(Process $server): void
{
    if (! $server->isRunning()) {
        return;
    }

    if (PHP_OS_FAMILY === 'Windows') {
        (new Process(['taskkill', '/F', '/T', '/PID', (string) $server->getPid()]))->run();
    }

    $server->stop(2);
}
