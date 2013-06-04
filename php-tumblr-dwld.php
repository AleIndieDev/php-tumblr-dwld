<?php 
/****************************************************************************/
/*																			*/
/* PHP-TUMBLR-DWLD 															*/
/*																			*/
/****************************************************************************/
/*																			*/
/* Description: A PHP script for make a backup or download all images 		*/
/*				through all pages from one or more tumblr accounts			*/
/* GitHub Url:  https://github.com/elalealvaro/php-tumblr-dwld 				*/
/* Version: 	0.1 														*/
/*																			*/
/*																			*/
/* Author:  	Alejandro Alvaro 											*/
/* Website: 	http://www.alejandroalvaro.com.ar/ 							*/
/* GitHub:  	http://www.github.com/elalealvaro							*/
/*																			*/
/*																			*/
/* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,			*/
/* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 		*/
/* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 					*/
/* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE 	*/
/* LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 	*/
/* OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION 	*/
/* WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE. 			*/
/*																			*/
/****************************************************************************/

ini_set('display_errors', 'Off');
ini_set('display_startup_errors', 'Off');
error_reporting(0);

define('PAGE_LIMIT', 1000);
define('URL_MASK', 'http://%s.tumblr.com/');
define('CURRENT_PATH', dirname(__FILE__) . '/');

// get filename widh tumblr account list
if(isset($argv[1]))
{
	$file = CURRENT_PATH . $argv[1];
}

if(strlen($file) && file_exists($file))
{
	$tumblr_pages = file($file);

	foreach($tumblr_pages as $tumblr) 
	{
		$url_orig = sprintf(URL_MASK, trim($tumblr));
		print_message(sprintf("Get all images for url %s", $url_orig));

		$next = true;
		$page = 0;

		// get all pages
		while($next)
		{
			$page++;

			if($page == 1)
			{
				$url = $url_orig;
			}
			else
			{
				$url = $url_orig . "page/" . $page;
			}

			print_message(sprintf("Get page %d, url %s", $page, $url));

			$error = '';
			try
			{
				$handle = curl_init($url);
				curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($handle, CURLOPT_HEADER, 0);

				$response = curl_exec($handle);

				if(strlen($response))
				{
					$doc = new DOMDocument();
					$doc->strictErrorChecking = FALSE;
				    $doc->loadHTML($response);
				    $imageTags = $doc->getElementsByTagName('img');

				    $result = array();
				    if(count($imageTags))
				    {
					    foreach($imageTags as $tag) 
					    {
					        $result[] = $tag->getAttribute('src');
					    }
					}
				}
				else
				{
					$error = sprintf("No data for %s", $url);
				}
			}
			catch(Exception $e) 
			{
				$error = sprintf("Something wrong happend when we try to get %d page", $page);
			}

			if(!strlen($error))
			{
			    $no_pics = true;
			    $download = array();

			    $album_path = CURRENT_PATH . trim($tumblr);
			    if(count($result))
			    {
			    	if(!file_exists($album_path)) 
			    	{
			    		print_message(sprintf("Create album dir '%s'", $album_path));
					    mkdir($album_path, 0755, true);
					}

			    	foreach($result as $img_url) 
			    	{
			    		if(strpos($img_url, '.media.tumblr.com') && !strpos($img_url, 'avatar_'))
			    		{
			    			$no_pics = false;

			    			$parts = explode("/", $img_url);
			    			$filename = end($parts);
							
							$save_path = $album_path . "/" . $filename;

			    			$download[] = array(
			    				'original'     => $img_url,
			    				'destination'  => $save_path
			    			);
			    		}
			    	}
			    }

			    if(count($download))
			    {
			    	print_message(sprintf("Start downloading %d images", count($download)));
			    	foreach($download as $v) 
			    	{
			    		print_message(sprintf("Saving %s", $v['original']));
			    		$image_file  = file_get_contents($v['original']);
						file_put_contents($v['destination'], $image_file);
			    	}
			    }

				if($no_pics || $page >= PAGE_LIMIT)
				{
					// no more pages
					$next = false;
				}
			}
			else
			{
				print_message($error);
			}

			// restart
			curl_close($handle);
			unset($handle);
			unset($response);
			unset($doc);			
		}
	}
}
else
{
	print_message("File is missing or not specified");
}

function print_message($message)
{
	print sprintf("[%s] - %s\n", date('Y-m-d H:i:s', strtotime('now')), $message);
}

?>
