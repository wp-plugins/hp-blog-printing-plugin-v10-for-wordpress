<?php

if (empty($wp)) {
	$wp_root = dirname(__FILE__);
	$wp_root = dirname($wp_root);
	$wp_root = dirname($wp_root);
	$wp_root = dirname($wp_root);

	set_include_path(get_include_path() . PATH_SEPARATOR . $wp_root);
	require_once('wp-config.php');
	wp('feed=atom');
}

// Check credentials
$user = $_GET['username'];
$pass = $_GET['password'];
if (validateCredentials($user, $pass)) {
	// Check the __mode
	if ($_GET['__mode'] == 'entries')
	{
		entries();
	} 
	elseif ($_GET['__mode'] == 'entry')
	{
		entry();
	}
	elseif ($_GET['__mode'] == 'full_entries')
	{
		full_entries();
	}
} else {
	echo 'Invalid credentials.';
}

function validateCredentials($user, $pass)
{
	$true_user = 'blogPrintUser';
	$true_pass = get_option("blogPrint_sub_password");
	if ($true_user == $user && $true_pass == $pass)
		return true;
	return false;
}

function entries()
{
	header('Content-type: application/atom+xml; charset=' . get_option('blog_charset'), true);
	$more = 1;
?>
<?php echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<feed
	  xmlns="http://www.w3.org/2005/Atom"
	  xmlns:thr="http://purl.org/syndication/thread/1.0"
	  xmlns:blogprint="http://smartwebprinting.com/blogPrint"
	  xml:lang="<?php echo get_option('rss_language'); ?>"
	  xml:base="<?php bloginfo_rss('home') ?>/wp-atom.php">
	
	<title type="text"><?php bloginfo_rss('name'); ?></title>

	<updated><?php echo mysql2date('Y-m-d\TH:i:s\Z', get_lastpostmodified('GMT')); ?></updated>
	<generator uri="http://wordpress.org/" version="<?php bloginfo_rss('version'); ?>">WordPress</generator>

	<link rel="alternate" type="text/html" href="<?php bloginfo_rss('home') ?>" />
	<id><?php bloginfo('atom_url'); ?></id>
	<author><name /></author>
	
<?php
		global $post;
		$myposts = get_posts('numberposts=' . $_GET[limit] . '&offset=' . $_GET[offset]);
		foreach($myposts as $post) :
		setup_postdata($post);
		if (empty($post->post_password)) { // if there's not a password
	?>
		<entry>
			<title type="text/html"><![CDATA[<?php the_title_rss() ?>]]></title>
			<link rel="alternate" type="text/html" href="<?php permalink_single_rss() ?>" />
			<id><?php permalink_single_rss() ?></id>
			<blogprint:postid><?php the_id(); ?></blogprint:postid>
			<updated><?php echo get_post_time('Y-m-d\TH:i:s\Z', true); ?></updated>
			<published><?php echo get_post_time('Y-m-d\TH:i:s\Z', true); ?></published>
		</entry>
			
<?php } endforeach ; ?>
</feed>
<?php
}

function full_entries()
{
	// Parse the list of IDs into an array
	$entry_list = explode(',', $_GET['entry_list']);
	
	header('Content-type: application/atom+xml; charset=' . get_option('blog_charset'), true);
	
?>
<?php echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<feed
	  xmlns="http://www.w3.org/2005/Atom"
	  xmlns:thr="http://purl.org/syndication/thread/1.0"
	  xmlns:blogprint="http://smartwebprinting.com/blogPrint"
	  xml:lang="<?php echo get_option('rss_language'); ?>"
	  xml:base="<?php bloginfo_rss('home') ?>/wp-atom.php">
	
	<title type="text"><?php bloginfo_rss('name'); ?></title>

	<updated><?php echo mysql2date('Y-m-d\TH:i:s\Z', get_lastpostmodified('GMT')); ?></updated>
	<generator uri="http://wordpress.org/" version="<?php bloginfo_rss('version'); ?>">WordPress</generator>

	<link rel="alternate" type="text/html" href="<?php bloginfo_rss('home') ?>" />
	<id><?php bloginfo('atom_url'); ?></id>
	<author><name /></author>
	
<?php
		global $post;
		global $more;
		$more = 1;
		
		foreach($entry_list as $entry_id) :
			$post = get_post($entry_id);
			setup_postdata($post);
			if (empty($post->post_password)) { // if there's not a password
	?>
		<entry>
			<author>
				<name><?php the_author(); ?></name>
			</author>
			<title type="text/html"><![CDATA[<?php the_title_rss() ?>]]></title>
			<link rel="alternate" type="text/html" href="<?php permalink_single_rss() ?>" />
			<id><?php permalink_single_rss() ?></id>
			<blogprint:postid><?php the_id(); ?></blogprint:postid>
			<updated><?php echo get_post_time('Y-m-d\TH:i:s\Z', true); ?></updated>
			<published><?php echo get_post_time('Y-m-d\TH:i:s\Z', true); ?></published>
			<content type="text/html" xml:base="<?php permalink_single_rss() ?>">
				<![CDATA[<?php the_content() ?>]]>
			</content>
		</entry>
			
<?php } endforeach; ?>
</feed>
<?php
}

function entry()
{
header('Content-type: application/atom+xml; charset=' . get_option('blog_charset'), true);
global $more;
$more = 1;

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<feed
  xmlns="http://www.w3.org/2005/Atom"
  xmlns:thr="http://purl.org/syndication/thread/1.0"
  xmlns:blogprint="http://smartwebprinting.com/blogPrint"
  xml:lang="<?php echo get_option('rss_language'); ?>"
  xml:base="<?php bloginfo_rss('home') ?>/wp-atom.php">

	<title type="text"><?php bloginfo_rss('name'); ?></title>
	<updated><?php echo mysql2date('Y-m-d\TH:i:s\Z', get_lastpostmodified('GMT')); ?></updated>
	<generator uri="http://wordpress.org/" version="<?php bloginfo_rss('version'); ?>">WordPress</generator>
	<link rel="alternate" type="text/html" href="<?php bloginfo_rss('home') ?>" />
	<id><?php bloginfo('atom_url'); ?></id>

<?php
$myposts = query_posts('p=' . $_GET['id']);  
while (have_posts()) : the_post();
	if (empty($post->post_password)) { // if there's not a password
?>
	<entry>
		<author>
			<name><?php the_author(); ?></name>
		</author>
		<title type="text/html"><![CDATA[<?php the_title_rss() ?>]]></title>
		<link rel="alternate" type="text/html" href="<?php permalink_single_rss() ?>" />
		<id><?php permalink_single_rss() ?></id>
		<blogprint:postid><?php the_id(); ?></blogprint:postid>
		<updated><?php echo get_post_time('Y-m-d\TH:i:s\Z', true); ?></updated>
		<published><?php echo get_post_time('Y-m-d\TH:i:s\Z', true); ?></published>
		<content type="text/html" xml:base="<?php permalink_single_rss() ?>">
			<![CDATA[<?php the_content() ?>]]>
		</content>
	</entry>
<?php
	}
endwhile ; ?>
</feed>
<?php
}
?>


