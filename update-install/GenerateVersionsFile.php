<?php
// Generate versions for the update-install-mechanism
// This script is run by user to generate the versions.json file

// constants
$VERSIONS_FILE = "versions.json";

$output = [
    'stable' => '0.9.4',
    'versions' => [
        '0.9.0' => [
            'branch' => 'v0.9.0', // The branch name in the git repository
            'summary' => 'Initial release', // A short description of the release
            'date' => '2022-10-12', // The date of the release
            'changelog' => [ // A list of changes in this release
                'Added feature: Vhost management',
                'Added feature: Multiple php version management',
                'Added feature: SSH / Deploy keys management',
                // .... more changelog entries
            ]
        ],
        '0.9.1' => [
            'branch' => 'v0.9.1', // The branch name in the git repository
            'summary' => 'Pre release 1', // A short description of the release
            'date' => '2022-10-12', // The date of the release
            'changelog' => [ // A list of changes in this release
                'Removed all web-api related code',
                // .... more changelog entries
            ]
        ],
        '0.9.2' => [
            'branch' => 'v0.9.2', // The branch name in the git repository
            'summary' => 'Pre release 2', // A short description of the release
            'date' => '2022-10-12', // The date of the release
            'changelog' => [ // A list of changes in this release
                'Only updated docs',
                'This release showcases the update mechanism',
                // .... more changelog entries
            ]
        ],
        '0.9.3' => [
            'branch' => 'v0.9.3', // The branch name in the git repository
            'summary' => 'Pre release 3', // A short description of the release
            'date' => '2022-10-14', // The date of the release
            'changelog' => [ // A list of changes in this release
                'Docs updated',
                'Update info fetched from github',
                // .... more changelog entries
            ]
        ],
        '0.9.4' => [
            'branch' => 'master', // The branch name in the git repository
            'summary' => 'Pre release 4', // A short description of the release
            'date' => '2022-10-15', // The date of the release
            'changelog' => [ // A list of changes in this release
                'Certbot installation + configuration',
                // .... more changelog entries
            ]
        ],
        // .... add more versions here
    ]
];

echo 'Saving '.$VERSIONS_FILE.' ...' . PHP_EOL . 'Done.' . PHP_EOL;
file_put_contents(__DIR__ . '/' . $VERSIONS_FILE, json_encode($output, JSON_PRETTY_PRINT));
