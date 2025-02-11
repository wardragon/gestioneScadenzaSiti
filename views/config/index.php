<h1>Configuration</h1>
<?php if ($auth->isAdmin()): ?>
    <form method="post">
        <?php // ... (config form fields) ?>
        <button type="submit">Save</button>
    </form>
<?php else: ?>
    <p>You do not have permission to access this page.</p>
<?php endif; ?>
<a href="/">Back to Home</a>
