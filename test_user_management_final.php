<?php
require_once 'app/core/bootstrap.php';

echo 'Testing User Management Final Translations...' . PHP_EOL . PHP_EOL;

// Test English
$_SESSION['locale'] = 'en';
echo '=== English User Management ===' . PHP_EOL;
echo 'User Management: ' . \App\Core\t('users.user_management') . PHP_EOL;
echo 'Add User: ' . \App\Core\t('users.add_user') . PHP_EOL;
echo 'Create New User: ' . \App\Core\t('users.create_new_user') . PHP_EOL;
echo 'Back to Users: ' . \App\Core\t('users.back_to_users') . PHP_EOL;
echo 'Basic Information: ' . \App\Core\t('users.basic_information') . PHP_EOL;
echo 'Email or User ID: ' . \App\Core\t('users.email_or_user_id') . PHP_EOL;
echo 'All Statuses: ' . \App\Core\t('users.all_statuses') . PHP_EOL;
echo 'All Roles: ' . \App\Core\t('users.all_roles') . PHP_EOL;
echo 'Legacy Role: ' . \App\Core\t('users.legacy_role') . PHP_EOL;
echo 'Roles: ' . \App\Core\t('users.roles') . PHP_EOL;
echo 'Permissions: ' . \App\Core\t('users.permissions') . PHP_EOL;
echo 'Add Permission: ' . \App\Core\t('users.add_permission') . PHP_EOL;
echo 'No Permissions Found: ' . \App\Core\t('users.no_permissions_found') . PHP_EOL;

// Test Arabic
$_SESSION['locale'] = 'ar';
echo PHP_EOL . '=== Arabic User Management ===' . PHP_EOL;
echo 'User Management: ' . \App\Core\t('users.user_management') . PHP_EOL;
echo 'Add User: ' . \App\Core\t('users.add_user') . PHP_EOL;
echo 'Create New User: ' . \App\Core\t('users.create_new_user') . PHP_EOL;
echo 'Back to Users: ' . \App\Core\t('users.back_to_users') . PHP_EOL;
echo 'Basic Information: ' . \App\Core\t('users.basic_information') . PHP_EOL;
echo 'Email or User ID: ' . \App\Core\t('users.email_or_user_id') . PHP_EOL;
echo 'All Statuses: ' . \App\Core\t('users.all_statuses') . PHP_EOL;
echo 'All Roles: ' . \App\Core\t('users.all_roles') . PHP_EOL;
echo 'Legacy Role: ' . \App\Core\t('users.legacy_role') . PHP_EOL;
echo 'Roles: ' . \App\Core\t('users.roles') . PHP_EOL;
echo 'Permissions: ' . \App\Core\t('users.permissions') . PHP_EOL;
echo 'Add Permission: ' . \App\Core\t('users.add_permission') . PHP_EOL;
echo 'No Permissions Found: ' . \App\Core\t('users.no_permissions_found') . PHP_EOL;

echo PHP_EOL . 'User management final translation test completed!' . PHP_EOL;
