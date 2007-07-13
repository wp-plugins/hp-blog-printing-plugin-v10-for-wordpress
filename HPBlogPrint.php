<?php
/*
Plugin Name: HP Blog Printing
Plugin URI: http://developer.tabblo.com/
Description: Adds a PDF printing button to your WordPress blog.
Author: Hewlett-Packard
Version: 1.0.9d2
Author URI: http://developer.tabblo.com/

Requires WordPress 2.1 or later.

*/

/*  Copyright 2007 Hewlett-Packard Development Company, L.P.

    This program is free software; you can redistribute it and/or modify
    it under the terms of version 2 of the GNU General Public License as published by
    the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

define ("USE_DEV_MODE", 1);

function blogPrint_deactivate()
{
	$userInfo = blogPrint_validate_blog_print_user();
	
	$userName = $userInfo[0];
	$password = $userInfo[1];

	$adminKey = get_option("blogPrint_adminKey");
	$pbID = get_option("blogPrint_printButtonID");
	
	if ($adminKey && $pbID)
	{
		$query = "action=deletePrintButton";
		$query .= "&redirect=0";
		$query .= "&user=" . urlencode($userName);
		$query .= "&password=" . urlencode($password);
		$query .= "&adminKey=" . urlencode($adminKey);
		$query .= "&pbID=" . urlencode($pbID);
	}
	
	$buf = postData(blogPrint_get_servlet_url(), $query);

	delete_option("blogPrint_printButtonID");
	delete_option("blogPrint_adminKey");
	delete_option("blogPrint_sub_password");
}

function blogPrint_get_servlet_url()
{
	if (USE_DEV_MODE)
		return "http://mercator.criticalpath.com:8080/blogPrint/servlet";
	else
		return "http://blogprint2.smartwebprinting.com/blogPrint/servlet";
}

function blogPrint_get_button_title()
{
	if (!blogPrint_did_accept_terms_of_use()) return '';
	
	return "Print Blog Entries Now";	
}

function blogPrint_get_button_img()
{
	if (!blogPrint_did_accept_terms_of_use()) return '';
	
	$blogURL = get_option('home');
	return '<img src="' . $blogURL . '/wp-content/plugins/HPBlogPrint/printposts.png" width="107" height="20" style="border: none;" alt="Print Posts" />';
}

function widget_blogPrint($args)
{
	
	extract($args);
	echo $before_widget;
	
	blogPrint_get_print_button();
	
	echo $after_widget;
}

# Deprecated! Use blogPrint_get_print_button() instead.
function blogPrint_get_print_button_list()
{
	return blogPrint_get_print_button();
}

function blogPrint_get_print_button()
{
	global $post;
	global $posts; // Already populated by WP with the posts displayed on the current page
	$origPost = $post;
	
	// What kind of archive are we in -- witness my defiance of PHP naming conventions (such as they are)!
	$isMonth = is_month();
	$isCategory = is_category();
	$isDay = is_day();
	$isYear = is_year();
	$isAuthor = is_author();
	$isPaged = (isset($_GET['paged']) && !empty($_GET['paged']));
	// Or if none of the above and $posts contains one element, we must be an individual archive
	$isIndividual = !($isMonth || $isCategory || $isDay || $isYear || $isAuthor || $isPaged) && count($posts) == 1;

	$fetchIDs = null;
	$checkedIDs = null;
	
	if ($isIndividual)
	{		
		#Fetch list of succeeding entry IDs
		$nextEntries = array();
		for ($i = $post->ID + 1; $i < $post->ID + 6; $i++)
		{
			if (get_post($i) != null && empty(get_post($i)->post_password))
			{
				$nextEntries[] = $i;
			}
		}
		
		$post = $origPost;
		
		#Fetch list of preceeding entry IDs
		$prevEntries = array();
		for ($i = $post->ID; $i > $post->ID - 6; $i--)
		{
			if (get_post($i) != null && empty(get_post($i)->post_password))
			{
				$prevEntries[] = $i;
			}
		}

		$fetchIDs = array_merge(array_reverse($prevEntries), $nextEntries);
		$checkedIDs = array($post->ID);
	}
	else
	{
		$fetchIDs = array();
		foreach($posts as $aPost)
		{
			if (empty($aPost->post_password))
			{
				$fetchIDs[] = $aPost->ID;
			}
		}
		$checkedIDs = $fetchIDs;
	}
	
	
	blogPrint_validate_single_button();

	$pbID = get_option("blogPrint_printButtonID");
	
	if ($pbID)
	{
		$dialogHeight = 300;

		$href = blogPrint_get_servlet_url() . "?action=printDialog&pbID=" . urlencode($pbID);
		
		//Produce comma delimited lists of the IDs
		$fetchIDsStr = '';
		$checkedIDsStr = '';
		
		for ($count = 0; $count < count($fetchIDs); $count++)
		{
			$fetchIDsStr .= $fetchIDs[$count];
			if ($count != count($fetchIDs) - 1) { $fetchIDsStr .= ','; }
		}
		
		for ($count = 0; $count < count($checkedIDs); $count++)
		{
			$checkedIDsStr .= $checkedIDs[$count];
			if ($count != count($checkedIDs) - 1) { $checkedIDsStr .= ','; }
		}
		
		$href .= '&defaultCheckState=0&checkedPostIDs=' . $checkedIDsStr . '&fetchPostIDs=' .  $fetchIDsStr;
		 
		$target = 'blogPrintDialog_' . urlencode($pbID);
		
		echo " <a href=\"" . $href . "\"";
		echo " target=\"" . $target . "\"";
		echo " onclick=\"window.open('" . $href . "', '" . $target . "', ";
		echo "'width=480,height=" . $dialogHeight . ",top=300,left=500,toolbars=0,location=0,menubar=0,resizable=1,scrollbars=1').focus(); return false\">";
		echo blogPrint_get_button_img() . "</a>";
	}
}

function blogPrint_get_print_button_url()
{
	if (!blogPrint_did_accept_terms_of_use()) return '';
	
	blogPrint_validate_single_button();
	$pbID = get_option("blogPrint_printButtonID");
	if ($pbID)
	{
		echo blogPrint_get_servlet_url() . "?action=printDialog&pbID=" . urlencode($pbID);
	}
}


function blogPrint_register_sidebar()
{
	if (function_exists('register_sidebar_widget'))
		register_sidebar_widget('Printing', 'widget_blogPrint');
}

function blogPrint_validate_single_button()
{
	if (!blogPrint_did_accept_terms_of_use()) return;

	$title = get_bloginfo('name');

	$userInfo = blogPrint_validate_blog_print_user();
	
	$userName = $userInfo[0];
	$password = $userInfo[1];

	$pbID = get_option("blogPrint_printButtonID");

	$blogURL = get_option('home');
	
	$xmlrpcURL = get_bloginfo('wpurl') . '/wp-content/plugins/HPBlogPrint/print-interface.php';
	
	$servlet_url = blogPrint_get_servlet_url();

	global $blog_id;
	
	if (!$pbID)
	{
		$query = "action=createPrintButton";
		$query .= "&redirect=0";

		$query .= "&blogID=" . urlencode($blog_id);
		$query .= "&user=" . urlencode($userName);
		$query .= "&password=" . urlencode($password);
		$query .= "&blogURL=" . urlencode($blogURL);
		$query .= "&adminURL=" . urlencode($xmlrpcURL);
		$query .= "&title=" . urlencode($title);
		$query .= "&protocol=" . urlencode("wp-plugin");

		$buf = postData(blogPrint_get_servlet_url(), $query);

		$returnParams = array();
		
		parse_str($buf, $returnParams);
		
		$success = $returnParams['success'];
		
		if ($success == 'create')
		{
			$adminKey = $returnParams['adminKey'];
			$pbID = $returnParams['pbID'];
				
			add_option("blogPrint_adminKey", $adminKey, "Blog printing admin key for " . urlencode($title), 'yes');
			add_option("blogPrint_printButtonID", $pbID, "Print Button ID for " . urlencode($title), 'yes');
		}	
	}
}

function blogPrint_validate_blog_print_user()
{
	$userName = "blogPrintUser";
	$password = get_option("blogPrint_sub_password");
	
	if (!$password)
	{
		$password = randomstring(16);
		add_option("blogPrint_sub_password", $password, "Blog printing subscriber password", 'yes');
	}
			
	return array($userName, $password);
}

// utility functions

function randomstring($len) 
{
	$ret = '';
	
	mt_srand(crc32(microtime()));
	while($i<$len)
	{
		$ret .= chr(mt_rand(33,126));
		$i++;
	}
	
	return base64_encode($ret);
}

function postData($url,$data)
{
	$urlComponents = parse_url($url);

	$port = $urlComponents['port'];
	if (!$port)
	{
		$port = 80;
	}
	
	$host = $urlComponents['host'];
	$path = $urlComponents['path'];
		
	$fp = fsockopen($host, $port);

	fputs($fp, "POST $path HTTP/1.1\r\n");
	fputs($fp, "Host: $host\r\n");
	fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
	fputs($fp, "Content-length: " . strlen($data) . "\r\n");
	fputs($fp, "User-Agent: HP Blog Printing Wordpress Plugin\r\n");
	
	fputs($fp, "Connection: close\r\n\r\n");

	fputs($fp, $data);

	$doneWithHeader = false;
	
	while (!feof($fp))
	{
		$line = fgets($fp, 1024);
		$line = trim($line);
				
		if ($line == "")
		{
			$doneWithHeader = true;
		}
		
		if ($doneWithHeader)
			$buf .= $line;
	}
	
	fclose($fp);
	return $buf;
}

function blogPrint_did_accept_terms_of_use() {
	return yes;
	#return get_option('blogPrint_did_accept_tou');
}

// Insert the mt_add_pages() sink into the plugin hook list for 'admin_menu'
// defeated the admin page for MU at present
//add_action('admin_menu', 'blogPrint_add_pages');

function blogPrint_options_page() {
	if (empty($_POST['blogPrint_submit']) && !blogPrint_did_accept_terms_of_use()) {
		blogPrint_options_page_form(false);
	} else {
		blogPrint_options_page_form(true);
		update_option("blogPrint_did_accept_tou", true, 'Determines whether the user has accepted the HP Blog Printing Terms of Use', 'yes');
	}
}

function blogPrint_options_page_form($accepted) {
?>
	<div class="wrap">
		<h2><?php _e('Hewlett-Packard Co. Print Service Terms of Use'); ?></h2>
		<fieldset class="options">
			<?php 
				if (!$accepted) { 
					echo 'You must accept the terms of use before the plugin will activate.';
				} else {
					echo 'Terms accepted. The plugin is active.';
				}
			 ?>
			
			<p><textarea style="height: 300px; width: 600px; background-color: white;" readonly="true" disabled="<?php echo ($accepted) ? 'true' : 'false'; ?>">
Thank you for using the Hewlett-Packard Co. Smart Web Printing Blog Print service featuring the Hewlett-Packard Co. Print API (collectively the "Service"), you ("You") accept and agree to be bound by this Agreement as well as the additional terms and conditions set forth in the Hewlett-Packard Co. website’s terms of use (collectively the "Terms").

1. Service.

1.1 Description of Service. The application programming interface (“API”) consists of software that allows You to display a print button on your website and to download code necessary to access the Hewlett-Packard Co. Smart Web Printing Blog Print service, subject to the limitations and conditions described below. The API is limited to allowing You to display the print button image only and does not provide You with the ability to access the underlying print software or any Hewlett-Packard Co. service except as expressly allowed by Hewlett-Packard Co. for the purpose of enabling You and Your end users to access the Service.  

1.2 Subject to the limitations and conditions described below, You may use the Service information to format your blog for printing for Your end users.

1.3 Modifications. Hewlett-Packard Co. reserves the right to release subsequent versions of the Service and API and to require You to obtain and use the most recent version. Hewlett-Packard Co. may modify the Terms at any time with or without notice. If a modification is unacceptable to You, You may cancel the Service by uninstalling the software from Your Site. If You continue to use the Service on any Site, You will be deemed to have accepted the modifications. 

1.4 Appropriate Conduct and Prohibited Uses. The Service may be used only for services that are generally accessible to consumers without charge. You agree that You are responsible for your own conduct and content while using the Service and for any consequences thereof. You agree to use the Service only for purposes that are legal, proper and in accordance with these Terms and any applicable Hewlett-Packard Co. policies or guidelines. By way of example, and not as a limitation, You agree that when using the Service, You will not: 
	- defame, abuse, harass, stalk, threaten or otherwise violate the legal rights (such as rights of privacy and publicity) of others; 
	- upload, post, email or transmit or otherwise make available any inappropriate, defamatory, infringing, obscene, or unlawful Content; 
	- upload, post, email or transmit or otherwise make available any Content that infringes any patent, trademark, copyright, trade secret or other proprietary right of any party, unless You are the owner of the Rights or have the permission of the owner to post such Content; 
	- upload, post, email or transmit or otherwise make available messages that promote pyramid schemes, chain letters or disruptive commercial messages or advertisements, or anything else prohibited by law, these Terms or any applicable policies or guidelines. 
	- download any file posted by another that You know, or reasonably should know, that cannot be legally distributed in such manner; 
	- impersonate another person or entity, or falsify or delete any author attributions, legal or other proper notices or proprietary designations or labels of the origin or source of software or other material; 
	- restrict or inhibit any other user from using and enjoying Hewlett-Packard Co. services; 
	- use Hewlett-Packard Co. services for any illegal or unauthorized purpose; 
	- remove any copyright, trademark or other proprietary rights notices contained in or on Hewlett-Packard Co. services; 
	- interfere with or disrupt Hewlett-Packard Co. services or servers or networks connected to Hewlett-Packard Co. services, or disobey any requirements, procedures, policies or regulations of networks connected to Hewlett-Packard Co. services; 
	- use any robot, spider, site search/retrieval application, or other device to retrieve or index any portion of Hewlett-Packard Co. services or collect information about users for any unauthorized purpose; 
	- submit Content that falsely expresses or implies that such Content is sponsored or endorsed by Hewlett-Packard Co.; 
	- create user accounts by automated means or under false or fraudulent pretenses; 
	- promote or provide instructional information about illegal activities or promote physical harm or injury against any group or individual; or 
	- transmit any viruses, worms, defects, Trojan horses, or any items of a destructive nature. 
International users agree to comply with their own local rules regarding online conduct and acceptable content, including laws regulating the export of data to the United States or your country of residence. In addition, the Service may not be used: (a) for or with real time route guidance (including without limitation, turn-by-turn route guidance and other routing that is enabled through the use of a sensor), or (b) for, or in connection with, any systems or functions for automatic or autonomous control of vehicle behavior.

1.5 Advertising. The Service does not include the advertising associated in the blog in the formatted .pdf file created by the Service. 

1.6 Hewlett-Packard Co. Rights. For purposes of the Terms, "Intellectual Property Rights" shall mean any and all rights existing from time to time under patent law, copyright law, semiconductor chip protection law, moral rights law, trade secret law, trademark law, unfair competition law, publicity rights law, privacy rights law, and any and all other proprietary rights, and any and all applications, renewals, extensions and restorations thereof, now or hereafter in force and effect worldwide. As between You and Hewlett-Packard Co., You acknowledge that Hewlett-Packard Co. owns all right, title and interest, including without limitation all Intellectual Property Rights, in and to the Service and that You shall not acquire any right, title, or interest in or to the Service, except as expressly set forth in the Terms. 

1.7 Digital Millennium Copyright Act. It is Hewlett-Packard Co.'s policy to respond to notices of alleged infringement that comply with the Digital Millennium Copyright Act. 

1.8 License. Hewlett-Packard Co. hereby grants You a royalty-free, nonexclusive, nontransferable, non-sublicensable license in the object code of the API to download, install, use, reproduce (for archival and back-up purposes only), and compile the API.  The foregoing rights are solely to all You and Your end users to access the Services provided by Hewlett-Packard Co. hereunder.  

1.9 End Users. You agree to use with your end users who access the Hewlett-Packard Co. Services terms with protections and restrictions at least as stringent as those which You use for Your own software of a similar nature.  

2.0 Privacy Policy. Hewlett-Packard Co.'s collection and use of personal information is governed by Hewlett-Packard Co.'s Privacy Policy, available at Privacy Policy. You understand and agree that Hewlett-Packard Co. may access, preserve, and disclose Your personal information and the contents of Your account if required to do so by law or in a good faith belief that such access preservation or disclosure is reasonably necessary to comply with a legal process or to protect the rights, property and/or safety of Hewlett-Packard Co., its affiliates or the public. Personal information collected by Hewlett-Packard Co. may be stored and processed in the United States or any other country in which Hewlett-Packard Co. or its agents maintain facilities. By using the Service, You consent to any such transfer of information outside of your country. 

2.1 INDEMNITY. You agree to hold harmless and indemnify Hewlett-Packard Co., and its subsidiaries, affiliates, officers, agents, and employees, advertisers or partners, from and against any third party claim arising from or in any way related to Your use of the Service, violation of these Terms, or any other actions connected with use of Hewlett-Packard Co. services, including any liability or expense arising from all claims, losses, damages (actual and consequential), suits, judgments, litigation costs and attorneys' fees, of every kind and nature. In such a case, Hewlett-Packard Co. will provide You with written notice of such claim, suit or action. 

2.2 DISCLAIMER OF WARRANTIES 
YOU EXPRESSLY UNDERSTAND AND AGREE THAT: 
a. YOUR USE OF THE SERVICE IS AT YOUR SOLE RISK. THE SERVICE IS PROVIDED ON AN "AS IS" AND "AS AVAILABLE" BASIS. HEWLETT-PACKARD CO. EXPRESSLY DISCLAIMS ALL WARRANTIES OF ANY KIND, WHETHER EXPRESS OR IMPLIED, INCLUDING, BUT NOT LIMITED TO THE IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT.
b. HEWLETT-PACKARD CO. MAKES NO WARRANTY THAT (i) THE SERVICE WILL MEET YOUR REQUIREMENTS, (ii) THE SERVICE WILL BE UNINTERRUPTED, TIMELY, SECURE, OR ERROR-FREE, (iii) THE RESULTS THAT MAY BE OBTAINED FROM THE USE OF THE SERVICE WILL BE ACCURATE OR RELIABLE, (iv) THE QUALITY OF ANY PRODUCTS, SERVICES, INFORMATION, OR OTHER MATERIAL PURCHASED OR OBTAINED BY YOU THROUGH THE SERVICE WILL MEET YOUR EXPECTATIONS, AND (V) ANY ERRORS IN THE SOFTWARE WILL BE CORRECTED.
c. ANY MATERIAL DOWNLOADED OR OTHERWISE OBTAINED THROUGH THE SERVICE IS DONE AT YOUR OWN DISCRETION AND RISK AND THAT YOU WILL BE SOLELY RESPONSIBLE FOR ANY DAMAGE TO YOUR COMPUTER SYSTEM OR LOSS OF DATA THAT RESULTS FROM THE DOWNLOAD OF ANY SUCH MATERIAL.
d. NO ADVICE OR INFORMATION, WHETHER ORAL OR WRITTEN, OBTAINED BY YOU FROM HEWLETT-PACKARD CO. OR THROUGH OR FROM HEWLETT-PACKARD CO. SERVICES SHALL CREATE ANY WARRANTY NOT EXPRESSLY STATED IN THE TERMS.

2.3 LIMITATION OF LIABILITY 
YOU EXPRESSLY UNDERSTAND AND AGREE THAT HEWLETT-PACKARD CO. SHALL NOT BE LIABLE TO YOU FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL OR EXEMPLARY DAMAGES, INCLUDING BUT NOT LIMITED TO, DAMAGES FOR LOSS OF PROFITS, GOODWILL, USE, DATA OR OTHER INTANGIBLE LOSSES (EVEN IF HEWLETT-PACKARD CO. HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES), RESULTING FROM: (i) THE USE OR THE INABILITY TO USE THE SERVICE; (ii) THE COST OF PROCUREMENT OF SUBSTITUTE GOODS AND SERVICES RESULTING FROM ANY GOODS, DATA, INFORMATION OR SERVICES PURCHASED OR OBTAINED OR MESSAGES RECEIVED OR TRANSACTIONS ENTERED INTO THROUGH OR FROM THE SERVICE; (iii) UNAUTHORIZED ACCESS TO OR ALTERATION OF YOUR TRANSMISSIONS OR DATA; (iv) STATEMENTS OR CONDUCT OF ANY THIRD PARTY ON THE SERVICE; OR (v) ANY OTHER MATTER RELATING TO THE SERVICE.

2.4 EXCLUSIONS AND LIMITATIONS 
SOME JURISDICTIONS DO NOT ALLOW THE EXCLUSION OF CERTAIN WARRANTIES OR THE LIMITATION OR EXCLUSION OF LIABILITY FOR INCIDENTAL OR CONSEQUENTIAL DAMAGES. ACCORDINGLY, SOME OF THE ABOVE LIMITATIONS OF SECTIONS 5 AND 6 MAY NOT APPLY TO YOU. 

2.5 Term. The term of the Terms shall commence on the date upon which agree to the Terms and shall continue in force thereafter, unless terminated as provided herein. 

2.6 Termination. Hewlett-Packard Co. may change, suspend or discontinue all or any aspect of the Service, including their availability, at any time, and may suspend or terminate Your use of the Service at any time. This includes, without limitation, the right to set, at Hewlett-Packard Co.'s own discretion and at any time, a maximum number of prints you may access through the service without Hewlett-Packard Co.'s prior written consent.  In addition, either party may terminate the Terms at any time, for any reason, or for no reason including, but not limited to, if You engage in any action that reflects poorly on Hewlett-Packard Co. or otherwise disparages or devalues the Hewlett-Packard Co. Brand Features or Hewlett-Packard Co.'s reputation or goodwill. If You desire to terminate the Terms, You must remove the Service from Your Site. 

2.7 Rejection of Application. Hewlett-Packard Co. shall have the right, in its sole discretion, to reject any request to use the Service at any time and for any reason, and such rejection shall render null and void the Terms between You and Hewlett-Packard Co.. Hewlett-Packard Co. shall not be liable to You for damages of any sort resulting from its decision to reject such a request. 

2.8 Effect of Termination. Upon the termination of the Terms for any reason (i) all license rights granted herein shall terminate and (ii) You shall immediately delete the print button, API, and all Hewlett-Packard Co. software from your site. 

2.9 Survival. In the event of any termination or expiration of the Terms for any reason, Sections 1.4, 1.6, 1.7, 1.8, 1.9, 2.1, 2.2, 2.3, 2.4, and 3.2 shall survive termination. Neither party shall be liable to the other party for damages of any sort resulting solely from terminating the Terms in accordance with its terms. 

3.0 Remedies. You acknowledge that Your breach of service/license restrictions contained herein may cause irreparable harm to Hewlett-Packard Co., the extent of which would be difficult to ascertain. Accordingly, You agree that, in addition to any other remedies to which Hewlett-Packard Co. may be legally entitled, Hewlett-Packard Co. shall have the right to seek immediate injunctive relief in the event of a breach of such sections by You or any of Your officers, employees, consultants or other agents.

3.1 Third Party Beneficiaries. Nothing in the Terms should be construed to confer any rights to third party beneficiaries. 

3.2 GENERAL INFORMATION 
Entire Agreement. The Terms constitute the entire agreement between You and Hewlett-Packard Co. and govern your use of the Service, superceding any prior agreements between You and Hewlett-Packard Co.. You also may be subject to additional terms and conditions that may apply when You use or purchase certain other Hewlett-Packard Co. services, affiliate services, third-party content or third-party software.

Choice of Law and Forum. The Terms and the relationship between You and Hewlett-Packard Co. shall be governed by the laws of the State of Delaware without regard to its conflict of law provisions.

Waiver and Severability of Terms. The failure of Hewlett-Packard Co. to exercise or enforce any right or provision of the Terms shall not constitute a waiver of such right or provision. If any provision of the Terms is found by a court of competent jurisdiction to be invalid, the parties nevertheless agree that the court should endeavor to give effect to the parties' intentions as reflected in the provision, and the other provisions of the Terms remain in full force and effect.

Statute of Limitations. You agree that regardless of any statute or law to the contrary, any claim or cause of action arising out of or related to use of Hewlett-Packard Co. services or the Terms must be filed within one (1) year after such claim or cause of action arose or be forever barred. 

The section headings in the Terms are for convenience only and have no legal or contractual effect.
			</textarea><br />
			<a href="http://welcome.hp.com/country/us/en/privacy.html">Privacy Policy</a> | <a href="http://welcome.hp.com/country/us/en/termsofuse.html">Hewlett-Packard Company Website &mdash; Terms of use</a>
			</p>
			
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<input type="submit" <?php echo ($accepted) ? 'disabled="true"' : ''; ?>" name="blogPrint_submit" value="<?php _e('Accept Terms'); ?>" />
			</form>
		</fieldset>
	</div>
<?php
}

function blogPrint_add_pages() {
	#add_options_page('HP Blog Printing', 'Printing', 8, 'hp_blogprint_options', 'blogPrint_options_page');
}

blogPrint_validate_single_button();

add_action('plugins_loaded', 'blogPrint_register_sidebar');
add_action('deactivate_HPBlogPrint/HPBlogPrint.php', 'blogPrint_deactivate');
add_action('admin_menu', 'blogPrint_add_pages');


?>
