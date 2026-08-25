<?php
/**
 * Default view for the `home` page.
 *
 * No controller exists at pages/home/controllers/, so the framework falls back
 * to the base SEController, whose actionDefault() renders the view named after
 * the page — this file.
 */

use app\core\SNEMLibrary;

$snem = SNEMLibrary::app();
?>
<h1>SNEM Starter</h1>
<p class="lede"><?= htmlspecialchars($snem->params('tagline')) ?></p>

<p>This app is a working tour of the framework. Each page below demonstrates one piece:</p>

<ul class="tour">
    <li>
        <a href="?page=posts">Posts</a>
        — a controller with several actions, SQL through the <code>#__</code>
        prefix, views with arguments, and a repeated partial.
    </li>
    <li>
        <a href="?page=posts&amp;task=post&amp;action=api">Posts as JSON</a>
        — the same data returned through <code>jsonResponse()</code>.
    </li>
    <li>
        <a href="?page=login">Sign in</a>
        — session auth via an <code>SEUserIdentity</code> subclass.
    </li>
    <li>
        <a href="?page=admin">Admin</a>
        — a protected page (<code>checkAccess()</code>) that writes through an
        Active Record model.
    </li>
    <li>
        <a href="?page=nope">A missing page</a>
        — falls through to the 404 template.
    </li>
</ul>

<h2>Where things live</h2>
<pre class="tree">
config.php            application configuration
index.php             front controller
app/models/           shared classes, autoloaded by short name
app/helpers/          TextHelper -&gt; $snem-&gt;helper-&gt;text
pages/{page}/         one directory per page
  {page}.php          the page class
  controllers/        XController.php -&gt; ?task=x
  views/              rendered by $this-&gt;render()
templates/default/    site-wide layout
</pre>
