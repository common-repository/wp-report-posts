<?
/*
Plugin Name: WP Report Posts
Plugin URI: http://www.ukbusinesslistings.info/
Description: This wordpress plug-in will put a link to each posts and pages to report that particluar post or page. The reported posts will be available in the backend.
Author: Thamizhchelvan
Version: 1.0
Author URI: http://thamizhchelvan.com/
*/


//We don't know which is the plugin path while the captcha call
$out_put = explode("wp-content",dirname(__FILE__));
$install_path = $out_put[0];

/**
*Function to check whether captcha can be added or not
*@return: boolean
*@author: thamizhchelvan
*/
function is_captcha()
{
	if(function_exists('imagestring'))
	{
		return true;
	}

	return false;

}

//captch or form call
if(isset($_REQUEST['req']))
{
	include_once($install_path."wp-config.php");
	session_start();

	switch($_REQUEST['req'])
	{
		case "captcha":
        {
			$num_chars = 6; //number of characters for captcha image
			$characters = array_merge(range(0,9),range('A','Z'),range('a','z')); // captcha characters
            shuffle($characters);
            $captcha_text = "";

            for($i=0;$i<$num_chars;$i++)
			{
				$captcha_text .= $characters[rand(0,count($characters)-1)];
			}

			$_SESSION['captcha'] = $captcha_text;
            header("Content-type: image/png"); // setting the content type as png
            $captcha_image = imagecreatetruecolor(140, 30);
            $captcha_background = imagecolorallocate($captcha_image, 225, 238, 221); //setting captcha background colour
            $captcha_text_colour = imagecolorallocate($captcha_image, 58, 94, 47); //setting captcha text colour
            imagefilledrectangle($captcha_image, 0, 0, 140, 29, $captcha_background); 
            imagestring($captcha_image, 5,20,11,$captcha_text,$captcha_text_colour);
            imagepng($captcha_image);
            imagedestroy($captcha_image);
			exit;
		}
		case "reportform":
		{
			$cust_error = "";
			if(isset($_POST['rid']))
			{
				if(is_captcha() && $_SESSION['captcha'] != $_POST['captcha_value'])
				{
                	$cust_error =  "Invalid Captcha";
                }
                else
                {
                	$post_id = (int) $_POST['pid'];
					$reportid = (int) $_POST['rid'];
					$reporttime = time();
					$reportip = $_SERVER['REMOTE_ADDR'];
					
					//check for previous reporting for this post,ip & type
					$sql_check_report = "SELECT report_id FROM `".$GLOBALS['table_prefix']."reportposts`
					                     WHERE post_id=$post_id AND report_type = $reportid AND report_ip='$reportip'";
					
					$check_result = mysql_query($sql_check_report) or die(mysql_error());
					//already reported give js alert
					if(mysql_num_rows($check_result) > 0)
					{
						echo '<script lanuage="javascript">
					          alert("You Already Reported");
                              window.close();
                              </script>';
                    	exit;
                    }

                    $sql_add_report = "INSERT INTO `".$GLOBALS['table_prefix']."reportposts`
					  			       VALUES('','$post_id','$reportid','$reporttime','$reportip')";

                    @mysql_query($sql_add_report);
					//reported
                    echo '<script lanuage="javascript">
					      alert("Thank You For Reporting");
                          window.close();
                          </script>';
                    exit;
				}

			}

?>
<center>

        <form method="post">
<b>Report This Post</b><BR /><BR /><BR /><BR /><BR />
<table style="border-collapse:collapse;" width="100%" border="0" >

        <caption><?php echo $cust_error;?></caption>

<?php
if(is_captcha())
{
?>
        <tr><td style="font-family:tahoma;font-size:10px;font-weight:bold">CAPTCHA</td><td><input name="captcha_value" type="text" size="6" maxlength="6">&nbsp;<img src="<?php echo $_SERVER['PHP_SELF'].'?req=captcha'; ?>">

        </td></tr>
	<?php
	}
	?>	

        <tr><td>&nbsp;

        <input type="hidden" name="pid" value="<?php echo $_GET['pid'];?>">

        <input type="hidden" name="rid" value="<?php echo $_GET['rid'];?>">

        </td><td align="center"><input type="submit" value="Report"></td></tr>

        </table>

        </form>

</center>

<?php

        exit;

           }

 

 

}

         exit;

}

 

if(!function_exists('report_uninstall'))

{

        function report_uninstall()

        {

                $sql_drop = "DROP TABLE IF EXISTS `".$GLOBALS['table_prefix']."reportposts`";

                mysql_query($sql_drop) or (mysql_error());

        }

}

 

//show the report option in pages and posts

if(!function_exists('make_report'))

{

        function make_report($content)

        {

                if(is_single() || is_page())

                        {

                                $report_table = $GLOBALS['table_prefix']."reportposts";
								
                                
								$report_settings = get_option('wp_report_conf');
								$report_conf = is_array($report_settings) ? $report_settings : unserialize($report_settings);

 

                                global $post;

                                $now_post_ = $post->ID;
								
								if(is_array($report_conf) && count($report_conf))
								{

                                $content .= '<fieldset>

                                <legend align="left">Report This Post</legend><ul>';

                                foreach($report_conf as $key => $value)

                                {

                                        $content .= '<li><a href="javascript:doReportPost(\''.$key.'\',\''.$now_post_.'\');">'.$value.'</a></li>';

                                }

                                $content .= '</ul></fieldset>';
								}
 

 

                        }

                return $content;

        }

 

}

add_action('the_content', 'make_report');

 

function add_wp_footer_js()
{

echo '<script language="JavaScript">

        function doReportPost(rid,pid)

        {

                var isOpened = null;

                var url = "'.get_option('siteurl').'/wp-content/plugins/wp-report-posts.php?req=reportform&rid="+rid+"&pid="+pid;

                isOpened = window.open(url, "", "width=300px, height=300px, resizable");

                if(isOpened == null)

                {

                        alert("Oops... Report Popup is blocked\nsDisable Your Popup Blocker");

                }

                else

                {

                        isOpened.moveTo(200,200);

                }

 

 

        }

</script>';

} 

add_action('wp_footer', 'add_wp_footer_js');
 

 

 

class admin_add_report

{

 

        //create the report table while activating the plugin

      function init()

      {

              $sql_create = 'CREATE TABLE IF NOT EXISTS `'.$GLOBALS['table_prefix'].'reportposts` (

                               `report_id` bigint(20) NOT NULL auto_increment,

                               `post_id` bigint(20) NOT NULL,

                               `report_type` int(11) NOT NULL,

                               `report_time` int(11) NOT NULL,

                               `report_ip` varchar(20) NOT NULL,

                               PRIMARY KEY  (`report_id`));';

                mysql_query($sql_create) or die(mysql_error());
				
				
				
				
				
			
            add_action('admin_menu', array('admin_add_report', 'add_option_page'));

      }

 function add_admin_popup()
				{
				
				echo '                <script language="javascript">

                function doProcess(url,msg)

                {

                        if(confirm(msg))

                        {

                        location.href = url;

                        }

                }

                </script>
                ';

				}
				

      function add_option_page()

      {

            if ( !function_exists('get_site_option') || is_site_admin() )

            {

                  add_options_page(

                              __('Report&nbsp;Posts'),

                              __('Report&nbsp;Posts'),

                              7,

                              str_replace("\\", "/", __FILE__),

                              array('admin_add_report', 'display_options')

                              );

                        add_options_page(

                              __('Configure&nbsp;Report&nbsp;Posts'),

                              __('Configure&nbsp;Report&nbsp;Posts'),

                              8,

                              str_replace("\\", "/", __FILE__."?conf=yes"),

                              array('admin_add_report', 'display_configure_options')

                              );

            }

      }
	//===========================================

//function for pagination

function doPaging($total,$perpage,$page,$curr_page,$extra='')

{

echo "<table>";

echo "<tr>";

 

$total_pages = round($total/$perpage)+1;

 

    if($total_pages <= 20)

      {

                     if($total_pages > 1)

                           {

                   for($i=1;$i<=$total_pages;$i++)

                           {

                           $reqPage = $page;

                              if($i == $curr_page)

                                       {

                                      echo "<td style='font-family:tahoma;font-size:12px;color:#722308'><b>[$i]</b></td>";

                                       }

                                       else

                                       {

                                       echo "<td style='font-family:tahoma;font-size:12px;color:#722308'>[<a class='admin_links' $extra href='$reqPage&curr_page=$i'>$i</a>]</td>";

                                       }

                           }

                           }

      }

      else

      {

 

 

   $start = $curr_page - 5;

   $start = ($start > 0)?$start:1;

 

 

      if(($start+20) < $total_pages)

      {

      $remain = $start+20;

      }

      else

      {

      $remain = $total_pages;

      }

 

      for($i=$start;$i<=$remain;$i++)

                           {

                           $reqPage = $page;

                              if($i == $curr_page)

                                       {

                                      echo "<td style='font-family:tahoma;font-size:12px;color:#722308'><b>[$i]</b></td>";

                                       }

                                       else

                                       {

                                       echo "<td style='font-family:tahoma;font-size:12px;color:#722308'>[<a class='admin_links' href='$reqPage&curr_page=$i'>$i</a>]</td>";

                                       }

                           }

 

 

 

 

 

 

 

      }

echo "</tr>";

echo "</table>";

}

//=======================================================================

 

 

 

        function display_configure_options()

        {

                $report_settings = get_option('wp_report_conf');
			    $report_conf = is_array($report_settings) ? $report_settings : unserialize($report_settings);

                $report_table = $GLOBALS['table_prefix']."reportposts";

                $btn_value = "ADD TYPE";

                $default_report_value = "";

                if(!is_array($report_conf))

                        $report_conf = array();

                //take insertion

                if(isset($_REQUEST['btnAction']))

                {

                        switch($_REQUEST['btnAction'])

                        {

                                case "ADD TYPE":

                                        {

                                                if(trim($_POST['report_type']) != '')

                                                {

                                                        $report_conf[] = trim($_POST['report_type']);

                                                        break;

                                                }

                                        }

                                case "DELCONF":

                                        {

 

                                                unset($report_conf[(int) $_REQUEST['confid']]);

                                                //remove all reported posts associated with this conf

                           $sql_remove_report = 'DELETE FROM '.$report_table.' WHERE report_type='.(int) $_REQUEST['confid'];
						   mysql_query($sql_remove_report);

                                                break;

 

                                        }

                                case "EDITCONF" OR "SAVE TYPE":

                                        {

                                                $default_report_value = isset($_POST['report_type'])? trim($_POST['report_type']) : $report_conf[(int) $_REQUEST['confid']];

                                                $btn_value = "SAVE TYPE";

                                                if(trim($_POST['report_type']) != '')

                                                {

 

                                                        $report_conf[(int) $_REQUEST['confid']] = trim($_POST['report_type']);

 

                                                }

                                                break;

                                        }

 

 

 

                        }

 

                        delete_option('wp_report_conf');

                        add_option('wp_report_conf',serialize($report_conf));

                }

 

 

                echo '<center><BR><table width="80%" style="border-collapse:collapse" border="0">';

                echo '<tr style="background-color:#E4F2FD;color:#464646"><td>Serial</td><td>Report Type</td><td>Edit</td><td>Delete</td></tr>';

                $serial = 0;

                foreach($report_conf as $key => $value)

                {

                        $serial++;

                        echo '<tr style="color:#464646"><td>'.$serial.'</td><td>'.$value.'</td><td><a href="options-general.php?page=wp-report-posts.php?conf=yes&btnAction=EDITCONF&confid='.$key.'">Edit</a></td><td><a href="javascript:doProcess(\'options-general.php?page=wp-report-posts.php?conf=yes&btnAction=DELCONF&confid='.$key.'\',\'Do You Really Want To Delete?\n Reported Posts Associated With This Configuration Also Deleted\');">Delete</a></td></tr>';

 

                }

                echo '</table></center>';

 

 

                echo '<center><BR><form method="post"><table width="50%"  style="border-collapse:collapse" border="1">';

                echo '<caption><a href="options-general.php?page=wp-report-posts.php?conf=yes">Add</a>/Edit Report Type</caption>';

                echo '<tr><td>Report Type</td><td><input class="code" value="'.$default_report_value.'" name="report_type" id="report_type" type="text" size="30">';

                echo '<tr><td>&nbsp;</td><td><input type="submit"  value="'.$btn_value.'" name="btnAction" class="button">';

                echo '</table></form></center>';

 

 

                

 

 

 

        }

 

 

 

      function display_options()

      {

              $custom_error='';

                $report_settings = get_option('wp_report_conf');
				$report_conf = is_array($report_settings) ? $report_settings : unserialize($report_settings);

              $post_table = $GLOBALS['table_prefix']."posts";

              $report_table = $GLOBALS['table_prefix']."reportposts";

            //taking deletion

              if(isset($_GET['mode']) && $_GET['mode'] == 'delete')

              {

                      $sql_delete = "DELETE FROM $report_table WHERE report_id=" . (int) $_GET['r_id'];

                      if(mysql_query($sql_delete))

                              {

                                    $custom_error='Deleted';

                              }

              }

                //displaying all reported posts

 

                //getting total records

                $total_records = mysql_query("SELECT count(report_id) AS Total FROM $report_table");

                $curr_page = (isset($_GET['curr_page']))? (int) $_GET['curr_page']:1;

                $perpage = 10;

                $start = ($curr_page-1)*$perpage;

                $sql_disp = "SELECT R.*, P.post_title FROM $report_table R, $post_table P WHERE R.post_id = P.ID ORDER BY R.report_time DESC LIMIT $start,$perpage";

                $disp_result = mysql_query($sql_disp) or die(mysql_error());

                echo '<center>';

                admin_add_report::doPaging(mysql_result($total_records,0,'Total'),$perpage,'options-general.php?page=wp-report-posts',$curr_page);

 

                echo '</center>';

                echo '<BR><table width="100%" style="border-collapse:collapse" border="0">';

                echo '<tr style="background-color:#E4F2FD;color:#464646"><td>Serial</td><td>Post</td><td>Report Type</td><td>Reported Time</td><td>Reported IP</td><td>Delete</td></tr>';

                $serial = ($curr_page-1)*$perpage;

                while($disp_row = mysql_fetch_assoc($disp_result))

                {

                        $serial++;

                        echo '<tr style="color:#464646"><td>'.$serial.'</td><td><a href="post.php?action=edit&post='.$disp_row['post_id'].'">'.$disp_row['post_title'].'</a></td><td>'.$report_conf[$disp_row['report_type']].'</td><td>'.date("Y-m-d h:i:sA",$disp_row['report_time']).'</td><td>'.$disp_row['report_ip'].'</td><td><a href="javascript:doProcess(\'options-general.php?page=wp-report-posts&r_id='.$disp_row['report_id'].'&mode=delete\',\'Do You Really Want To Delete?\');">Delete</a></td></tr>';

                }

                echo '</table>';

        }

 

}

admin_add_report::init();
add_action('admin_footer', array('admin_add_report', 'add_admin_popup'));	
add_action('deactivate_wp-report-posts.php', 'report_uninstall');
?>