<?php
// Generate versions for the update-install-mechanism
// This script is run by user to generate the versions.json file

// constants
$VERSIONS_FILE = "versions.json";

$output = [
    'stable' => '1.0',
    'versions' => [
        '1.0' => [
            'branch' => 'v1.0', // The branch name in the git repository
            'summary' => 'Initial release', // A short description of the release
            'date' => '2022-10-15', // The date of the release
            'changelog' => [ // A list of changes in this release
                'Added feature: Vhost management',
                'Added feature: Multiple php version management',
                'Added feature: SSH / Deploy keys management',
                // .... more changelog entries
            ]
            ],
    ]
];

echo 'Saving '.$VERSIONS_FILE.' ...' . PHP_EOL . 'Done.' . PHP_EOL;
file_put_contents(__DIR__ . '/' . $VERSIONS_FILE, json_encode($output, JSON_PRETTY_PRINT));
