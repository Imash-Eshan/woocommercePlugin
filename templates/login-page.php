<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if (!session_id()) {
    session_start();
}

?>
<div class="wrap">
    <h1>Login</h1>
    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
        <input type="hidden" name="action" value="newplugin_login">
        <table class="form-table">
            <tr>
                <th><label for="username">Username</label></th>
                <td><input type="text" name="username" id="username" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="password">Password</label></th>
                <td><input type="password" name="password" id="password" class="regular-text" required></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="newplugin_login" id="newplugin_login" class="button button-primary" value="Login">
        </p>
    </form>
</div>
