<?php

/**
 * This file is part of the MultipleFileUpload (https://github.com/jkuchar/MultipleFileUpload/)
 *
 * Copyright (c) 2013 Apps Dev Team s.r.o.
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */


namespace MultipleFileUpload\UI\Resumable;

use MultipleFileUpload\MultipleFileUpload;
use MultipleFileUpload\UI\AbstractInterface;
use Nette\Environment;
use SQLite3;

class Controller extends AbstractInterface {

	/**
	 * Getts interface base url
	 * @return type string
	 */
	function getBaseUrl() {
		return parent::getBaseUrl() . "resumable";
	}
	
	/**
	 * Is this upload your upload? (upload from this interface)
	 */
	public function isThisYourUpload() {
		$req = Environment::getHttpRequest();
		return (
			$req->getQuery("uploader") === "resumable"
		);
	}

	/**
	 * Handles uploaded files
	 * forwards it to model
	 */
	public function handleUploads() {
		
		//$token = $_REQUEST['resumableIdentifier'];
		$token = $_REQUEST['token'];
		
		if (empty($token)) {
			return;
		}
		
		$queueModel = MultipleFileUpload::getQueuesModel() // returns: IMFUQueuesModel
			->getQueue($token);
		$targetDir = $queueModel->getUploadedFilesTemporaryPath();
		
		


		////////////////////////////////////////////////////////////////////
		// THE SCRIPT
		////////////////////////////////////////////////////////////////////

		//check if request is GET and the requested chunk exists or not. this makes testChunks work
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {

				$temp_dir = $targetDir.'/'.$_GET['resumableIdentifier'];
				$chunk_file = $temp_dir.'/'.$_GET['resumableFilename'].'.part'.$_GET['resumableChunkNumber'];
				if (file_exists($chunk_file)) {
					header("HTTP/1.0 200 Ok");exit();
				} else {
					header("HTTP/1.0 404 Not Found");exit();
				}
		}



		// loop through files and move the chunks to a temporarily created directory
		if (!empty($_FILES)) foreach ($_FILES as $file) {

				// check the error status
				if ($file['error'] != 0) {
						self::_log('error '.$file['error'].' in file '.$_POST['resumableFilename']);
						continue;
				}

				// init the destination file (format <filename.ext>.part<#chunk>
				// the file is stored in a temporary directory
				$temp_dir = $targetDir.'/'.$_POST['resumableIdentifier'];
				$dest_file = $temp_dir.'/'.$_POST['resumableFilename'].'.part'.$_POST['resumableChunkNumber'];

				// create the temporary directory
				if (!is_dir($temp_dir)) {
						mkdir($temp_dir, 0777, true);
				}

				// move the temporary file
				if (!move_uploaded_file($file['tmp_name'], $dest_file)) {
						self::_log('Error saving (move_uploaded_file) chunk '.$_POST['resumableChunkNumber'].' for file '.$_POST['resumableFilename']);
				} else {

					$fileName = $_POST['resumableFilename'];
					$chunkSize = $_POST['resumableChunkSize'];
					$totalSize = $_POST['resumableTotalSize'];
					
					
					// count all the parts of this file
					$total_files = 0;
					foreach(scandir($temp_dir) as $file) {
							if (stripos($file, $fileName) !== false) {
									$total_files++;
							}
					}

					// check that all the parts are present
					// the size of the last part is between chunkSize and 2*$chunkSize
					if ($total_files * $chunkSize >=  ($totalSize - $chunkSize + 1)) {

							// create the final destination file 
							if (($fp = fopen($targetDir.'/'.$fileName, 'w')) !== false) {
									for ($i=1; $i<=$total_files; $i++) {
											fwrite($fp, file_get_contents($temp_dir.'/'.$fileName.'.part'.$i));
											self::_log('writing chunk '.$i);
									}
									fclose($fp);

									$queueModel->addFile(
										new \Nette\Http\FileUpload(array(
												'name' => $fileName,
												'type' => "",
												'size' => filesize($targetDir.'/'.$fileName),
												'tmp_name' => $targetDir.'/'.$fileName,
												'error' => UPLOAD_ERR_OK
										))
									);

							} else {
									self::_log('cannot create the destination file');
									return false;
							}

							// rename the temporary directory (to avoid access from other 
							// concurrent chunks uploads) and than delete it
							if (rename($temp_dir, $temp_dir.'_UNUSED')) {
									self::rrmdir($temp_dir.'_UNUSED');
							} else {
									self::rrmdir($temp_dir);
							}
					}
					
				}
		}
		
		
		
		
		
		
		
		
		
		
		/*
		
		
		//$token = $_REQUEST['resumableIdentifier'];
		$token = $_REQUEST['token'];
		
		if (empty($token)) {
			return;
		}

		// HTTP headers for no cache etc
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		// Settings
		$queueModel = MultipleFileUpload::getQueuesModel() // returns: IMFUQueuesModel
			->getQueue($token);
		$targetDir = $queueModel->getUploadedFilesTemporaryPath();
		$cleanupTargetDir = false; // Remove old files
		$maxFileAge = 60 * 60; // Temp file age in seconds
		// 5 minutes execution time
		@set_time_limit(5 * 60);

		// Uncomment this one to fake upload time
		// usleep(5000);
		// Get parameters
		$chunk = isset($_REQUEST['resumableChunkNumber']) ? $_REQUEST['resumableChunkNumber'] - 1 : 0;	// indexace od 1 na indexaci od nuly
		$chunks = isset($_REQUEST['resumableTotalChunks']) ? $_REQUEST['resumableTotalChunks'] : 0;
		$fileName = isset($_REQUEST['resumableFilename']) ? $_REQUEST['resumableFilename'] : '';
		$fileNameOriginal = $fileName;
		$fileName = sha1($token . $chunks . $fileNameOriginal);
		$targetFilePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

		
		// existuje chunk ?
		
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			$row = $queueModel->query("SELECT COUNT(*) AS count FROM files WHERE queueID = '". SQLite3::escapeString($queueModel->getQueueID()) ."' AND chunk = '". SQLite3::escapeString($chunk + 1) ."'")
				->fetchArray(SQLITE3_ASSOC);
			if ($row['count']) {
				self::_log($token .' chunk '. $chunk .' 200');
				header("HTTP/1.0 200 Ok");exit();
			} else {
				self::_log($token .' chunk '. $chunk .' 404');
				header("HTTP/1.0 404 Not Found");exit();
			}
		}
		
		// přidej chunk do DB
		
		if (!empty($_FILES)) foreach ($_FILES as $file) {
				self::_log($token .' chunk '. $chunk .' do DB');

			// check the error status
			if ($file['error'] != 0) {
					// TODO
					continue;
			}
			
			$fileName = $fileNameOriginal;
			$filePath = $file['tmp_name'];
		
			if($chunk == 0) {
				$queueModel->addFileManually($fileName, $chunk+1,$chunks);
			}
			$file = null;
			$nonChunkedTransfer = ($chunk == 0 AND $chunks == 0);
			$lastChunk = ($chunk+1) == $chunks;
			if($lastChunk OR $nonChunkedTransfer) {
				// Hotovo
				$file = new \Nette\Http\FileUpload(array(
						'name' => $fileNameOriginal,
						'type' => "",
						'size' => filesize($filePath),
						'tmp_name' => $filePath,
						'error' => UPLOAD_ERR_OK
				));
			}
			if ($file OR $chunk > 0) {
				$queueModel->updateFile($fileName, $chunk + 1, $file);
			}
		
			break;
		}
		
		
		// pokud máme všechny chunks, tak je spoj
		
		$row = $queueModel->query("SELECT COUNT(*) AS count FROM files WHERE queueID = '". SQLite3::escapeString($queueModel->getQueueID()) ."'")
				->fetchArray(SQLITE3_ASSOC);
		self::_log($token .' v DB celkem '. $row['count'] .' chunks');
		if ($row['count'] == $chunks) {
			self::_log($token .' chunk '. $chunk .' mame vse, spojujeme');
			
			$out = fopen($targetFilePath, "wb");
			if (! $out) {
				header("HTTP/1.0 404 Not Found");exit();
			}
			
			$result = $this->query("SELECT * FROM files WHERE queueID = '" . SQLite3::escapeString($this->getQueueID()) . "' ORDER BY chunk ASC");
			while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== FALSE) {
				$f = unserialize($row["data"]);
				if (!$f instanceof FileUpload)
					continue;
				
				// Read binary input stream and append it to temp file
				$in = fopen($f->getTemporaryFile(), "rb");

				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else {
					header("HTTP/1.0 404 Not Found");exit();
				}
				fclose($in);
			}
			fclose($out);
			
			$queueModel->delete();
			
			$queueModel->addFile(
				new \Nette\Http\FileUpload(array(
						'name' => $fileNameOriginal,
						'type' => "",
						'size' => filesize($targetFilePath),
						'tmp_name' => $targetFilePath,
						'error' => UPLOAD_ERR_OK
				))
			);
			
		}
		
		
		*/
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		/*
		// nový chunk připoj
		
		// Clean the fileName for security reasons
		$fileName = preg_replace('/[^\w\._]+/', '', $fileName);

		// Make sure the fileName is unique but only if chunking is disabled
		if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
			$ext = strrpos($fileName, '.');
			$fileName_a = substr($fileName, 0, $ext);
			$fileName_b = substr($fileName, $ext);

			$count = 1;
			while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
				$count++;
			$fileName = $fileName_a . '_' . $count . $fileName_b;
		}

		// Create target dir
		if (!file_exists($targetDir))
			@mkdir($targetDir);

		// Remove old temp files
		if (is_dir($targetDir) && ($dir = opendir($targetDir))) {
			while (($file = readdir($dir)) !== false) {
				$filePathTemp = $targetDir . DIRECTORY_SEPARATOR . $file;

				// Remove temp files if they are older than the max age
				if (preg_match('/\\.tmp$/', $file) && (filemtime($filePathTemp) < time() - $maxFileAge))
					@unlink($filePathTemp);
			}

			closedir($dir);
		} else {
			header("HTTP/1.0 404 Not Found");exit();
		}
		
		// Look for the content type header
		if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
			$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

		if (isset($_SERVER["CONTENT_TYPE"]))
			$contentType = $_SERVER["CONTENT_TYPE"];

		// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
		if (strpos($contentType, "multipart") !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				$tmpPath = $filePath . "-uploadTmp";
				move_uploaded_file($_FILES['file']['tmp_name'], $tmpPath); // Open base restriction bugfix
				// Open temp file
				$out = fopen($filePath, $chunk == 0 ? "wb" : "ab");
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen($tmpPath, "rb");

					if ($in) {
						while ($buff = fread($in, 4096))
							fwrite($out, $buff);
					} else {
						header("HTTP/1.0 404 Not Found");exit();
					}
					fclose($in);
					fclose($out);
					@unlink($tmpPath);
				} else {
					header("HTTP/1.0 404 Not Found");exit();
				}
			} else {
				header("HTTP/1.0 404 Not Found");exit();
			}
		} else {
			// Open temp file
			$out = fopen($filePath, $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");

				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else {
					header("HTTP/1.0 404 Not Found");exit();
				}

				fclose($in);
				fclose($out);
			} else {
				header("HTTP/1.0 404 Not Found");exit();
			}
		}

		if($chunk == 0) {
			$queueModel->addFileManually($fileName, $chunk+1,$chunks);
		}
		$file = null;
		$nonChunkedTransfer = ($chunk == 0 AND $chunks == 0);
		$lastChunk = ($chunk+1) == $chunks;
		if($lastChunk OR $nonChunkedTransfer) {
			// Hotovo
			$file = new \Nette\Http\FileUpload(array(
			    'name' => $fileNameOriginal,
			    'type' => "",
			    'size' => filesize($filePath),
			    'tmp_name' => $filePath,
			    'error' => UPLOAD_ERR_OK
			));
		}
		if ($file OR $chunk > 0) {
			$queueModel->updateFile($fileName, $chunk + 1, $file);
		}
		*/
		
		header("HTTP/1.0 200 Ok");exit();
		
		
		
		
		
		
		
		
		////////////////////////////////////////////////////////////////////
		// THE SCRIPT
		////////////////////////////////////////////////////////////////////

		
		/*
		//check if request is GET and the requested chunk exists or not. this makes testChunks work
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			$temp_dir = 'temp/'.$_GET['resumableIdentifier'];
			$chunk_file = $temp_dir.'/'.$_GET['resumableFilename'].'.part'.$_GET['resumableChunkNumber'];
			if (file_exists($chunk_file)) {
				header("HTTP/1.0 200 Ok");
			} else {
				header("HTTP/1.0 404 Not Found");
			}
		}
		*/
		


		/*
		// loop through files and move the chunks to a temporarily created directory
		if (!empty($_FILES)) foreach ($_FILES as $file) {

				// check the error status
				if ($file['error'] != 0) {
						_log('error '.$file['error'].' in file '.$_POST['resumableFilename']);
						continue;
				}

				// init the destination file (format <filename.ext>.part<#chunk>
				// the file is stored in a temporary directory
				$temp_dir = 'temp/'.$_POST['resumableIdentifier'];
				$dest_file = $temp_dir.'/'.$_POST['resumableFilename'].'.part'.$_POST['resumableChunkNumber'];

				// create the temporary directory
				if (!is_dir($temp_dir)) {
						mkdir($temp_dir, 0777, true);
				}

				// move the temporary file
				if (!move_uploaded_file($file['tmp_name'], $dest_file)) {
						_log('Error saving (move_uploaded_file) chunk '.$_POST['resumableChunkNumber'].' for file '.$_POST['resumableFilename']);
				} else {

						// check if all the parts present, and create the final destination file
						createFileFromChunks($temp_dir, $_POST['resumableFilename'], 
										$_POST['resumableChunkSize'], $_POST['resumableTotalSize']);
				}
		}
		*/
		
		
		
		
	}
	
	
	/**
	 * 
	 * Delete a directory RECURSIVELY
	 * @param string $dir - directory path
	 * @link http://php.net/manual/en/function.rmdir.php
	 */
	public static function rrmdir($dir) {
			if (is_dir($dir)) {
					$objects = scandir($dir);
					foreach ($objects as $object) {
							if ($object != "." && $object != "..") {
									if (filetype($dir . "/" . $object) == "dir") {
											self::rrmdir($dir . "/" . $object); 
									} else {
											unlink($dir . "/" . $object);
									}
							}
					}
					reset($objects);
					rmdir($dir);
			}
	}
	
	
	public static function _log($str) {

			// log to the output
			$log_str = date('d.m.Y').": {$str}\r\n";
			echo $log_str;

			// log to file
			if (($fp = fopen('/home/michal/www/zenskecykly.cz/private/temp/upload_log.txt', 'a+')) !== false) {
					fputs($fp, $log_str);
					fclose($fp);
			}
	}

	/**
	 * Renders interface to <div>
	 */
	public function render(MultipleFileUpload $upload) {
		$template = $this->createTemplate(dirname(__FILE__) . "/html.latte");
		$template->id = $upload->getHtmlId();
		return $template->__toString(TRUE);
	}

	/**
	 * Renders JavaScript body of function.
	 */
	public function renderInitJavaScript(MultipleFileUpload $upload) {
		$tpl = $this->createTemplate(dirname(__FILE__) . "/initJS.js");
		$tpl->token = $upload->getToken();
		$tpl->sizeLimit = $upload->maxFileSize;
		$tpl->maxFiles = $upload->maxFiles;
		
		// TODO: make creation of link nicer!
		$baseUrl = Environment::getContext()->getService('httpRequest')->url->baseUrl;
		$tpl->uploadLink = $baseUrl."?token=".$tpl->token."&uploader=resumable";
		$tpl->id = $upload->getHtmlId();
		return $tpl->__toString(TRUE);
	}

	/**
	 * Renders JavaScript body of function.
	 */
	public function renderDestructJavaScript(MultipleFileUpload $upload) {
		return $this->createTemplate(dirname(__FILE__) . "/destructJS.js")->__toString(TRUE);
	}

	/**
	 * Renders set-up tags to <head> attribute
	 */
	public function renderHeadSection() {
		return $this->createTemplate(dirname(__FILE__) . "/head.latte")->__toString(TRUE);
	}

}