<?php
/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2023 www.BillMinozzi.com
 * @ Modified time: 2023-02-05
 * */
if (!defined("ABSPATH")) {
    die('We\'re sorry, but you can not directly access this file.');
}



error_reporting(E_ALL);
ini_set("display_errors", 1);




//ini_set('max_execution_time', 15);
set_time_limit(180); //3600
ini_set("memory_limit", "128M"); // 800M); // 512M');

if (!function_exists("s3cloud_getHumanReadableSize")) {
    function s3cloud_getHumanReadableSize($bytes)
    {
        if ($bytes > 0) {
            $base = floor(log($bytes) / log(1024));
            $units = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"]; //units of measurement
            return number_format($bytes / pow(1024, floor($base)), 3) .
                " $units[$base]";
        } else {
            return "0 bytes";
        }
    }
}

if (!function_exists("s3cloud_record_debug")) {
    function s3cloud_record_debug($text)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "s3cloud_copy";
        if (!s3cloud_tablexist($table_name)) {
            return;
        }

        $txt = PHP_EOL . date("Y-m-d H:i:s") . " " . PHP_EOL;
        $txt .= __("Memory Usage Now:", "s3cloud");
        $txt .= function_exists("memory_get_usage")
            ? s3cloud_getHumanReadableSize(round(memory_get_usage(), 0))
            : 0;
        $txt .= PHP_EOL;
        $txt .= __("Memory Peak Usage:", "s3cloud") . " ";
        $txt .= s3cloud_getHumanReadableSize(memory_get_peak_usage());
        $txt .= PHP_EOL . $text . PHP_EOL;
        $txt .= "------------------------------";

        $query = "select debug from $table_name ORDER BY id DESC limit 1";
        $debug = $wpdb->get_var($query);
        $content = $debug . $txt;
        $r = $wpdb->query(
            $wpdb->prepare("UPDATE  `$table_name` SET debug = %s", $content)
        );
    }
}



global $folder_server;
global $folder_cloud;
global $server_cloud;
global $bucket_name;
global $s3cloud_copy_speed;
if (isset($_POST["radValue"])) {
    $s3cloud_copy_speed = sanitize_text_field($_POST["radValue"]);
} else {
    $s3cloud_copy_speed = "normal!!";
}
$s3cloud_time_limit = 90; // 120;
ini_set("max_execution_time", $s3cloud_time_limit);
set_time_limit($s3cloud_time_limit); //3600
if (isset($_POST["server_cloud"])) {
    $server_cloud = sanitize_text_field($_POST["server_cloud"]);
} else {
    die("Missing Post server_cloud");
}
if (isset($_POST["folder_server"])) {
    $folder_server = sanitize_text_field($_POST["folder_server"]);
} else {
    die("Missing Post folder_server");
}
if ($folder_server == "Root") {
    $folder_server = substr(ABSPATH, 0, strlen(ABSPATH) - 1);
}
if (isset($_POST["folder_cloud"])) {
    $folder_cloud = sanitize_text_field($_POST["folder_cloud"]);
} else {
    die("Missing Post folder_cloud");
}
if (isset($_POST["bucket_name"])) {
    $bucket_name = sanitize_text_field($_POST["bucket_name"]);
} else {
    die("Missing Post bucket_name");
}
if (
    !isset($_POST["nonce"]) ||
    !wp_verify_nonce(sanitize_text_field($_POST["nonce"]), "s3cloud_copy")
) {
    //////////////////////////////////////die("Nonce Fail");
}



s3cloud_ajax_copy();
function s3cloud_ajax_copy()
{
    global $bill_debug;
    global $s3cloud_copy_speed;
    global $wpdb;
    global $cond;
    global $s3cloud_files_array;
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $s3cloud_s3;
    global $s3cloud_config;

    $bill_debug = false;
    $st = s3cloud_get_copy_status();
    if ($st == null or $st == "end") {
        s3cloud_create_db_copy_files();

        s3cloud_copy_inic();

        if (defined("WP_MEMORY_LIMIT")) {
            s3cloud_record_debug("WordPress Memory Limit: " . WP_MEMORY_LIMIT);
        }
        s3cloud_record_debug("starting");
        // s3cloud_record_log("starting");
        $table_name = $wpdb->prefix . "s3cloud_copy";
        if (!s3cloud_tablexist($table_name)) {
            die("end");
        }
        $query = "update " . $table_name . " SET mystatus = 'counting'";
        $r = $wpdb->query($query);
        die("Counting Files...");
    }
    $st = s3cloud_get_copy_status();
    if ($st == "counting") {
        // s3cloud_record_log("counting files files to copy");
        s3cloud_record_debug("counting files to copy");

        $r = s3cloud_fetch_files($folder_server);

        if ($server_cloud == "server") {
            // die('fserver: '.$folder_server);
            $r = s3cloud_fetch_files($folder_server);
        } else {
            $r = s3cloud_scan_cloud($folder_cloud);
        }

        if (!is_array($r)) {
            if ($server_cloud == "server") {
                $text =
                    "s3cloud could not read the contents of your base WordPress directory. This usually indicates your permissions are so strict that your web server can\'t read your WordPress directory.";
            } else {
                $text =
                    "s3cloud could not read the contents of your base cloud directory.";
            }
            s3cloud_record_debug($text);
            die(
                "Fail to read, please, look the Scan Log tab. Click Cancel Button."
            );
        }

        $qfiles = (string) count($r);

        $table_name = $wpdb->prefix . "s3cloud_copy";
        $query =
            "update " .
            $table_name .
            " SET mystatus = 'loading', qfiles = '" .
            $qfiles .
            "'";
        $r = $wpdb->query($query);
        $txt = "Number of Files Found to Transfer: " . $qfiles;
        s3cloud_record_debug($txt);
        // s3cloud_record_log($txt);
        // s3cloud_record_log("loading files to transferr to table");
        s3cloud_record_debug("loading files to transfer to table");
        die("Loading files to copy...");
    }

    ////////////////////// COUNTING ///////////////////////////////

    $st = s3cloud_get_copy_status();
    if ($st == "loading") {
        global $wpdb;
        global $bill_debug;
        $s3cloud_quant_files = s3cloud_get_qfiles(); // total q files found
        $files_db = s3cloud_get_files_from_db(); // total...
        if ($bill_debug) {
            $s3cloud_quant_files = 2000;
        }
        if ($s3cloud_quant_files > count($files_db)) {
            if ($server_cloud == "server") {
                $s3cloud_files_array = s3cloud_fetch_files($folder_server);
            } else {
                $s3cloud_files_array = s3cloud_scan_cloud($folder_cloud);
            }

            $tomake = $s3cloud_quant_files;
            if ($s3cloud_copy_speed == "very_slow") {
                $maxtomake = 75;
            } elseif ($s3cloud_copy_speed == "slow") {
                $maxtomake = 150;
            } elseif ($s3cloud_copy_speed == "fast") {
                $maxtomake = 450;
            } elseif ($s3cloud_copy_speed == "very_fast") {
                $maxtomake = 600;
            } else {
                $maxtomake = 300;
            }

            // Find pointer...
            $table_name = $wpdb->prefix . "s3cloud_copy";
            $query = "select pointer from $table_name ORDER BY id DESC limit 1";

            $pointer = $wpdb->get_var($query);
            $ctd = 0;
            for ($i = $pointer; $i < $tomake; $i++) {
                if (!isset($s3cloud_files_array[$i])) {
                    die("not def i : " . var_export($s3cloud_files_array));
                }

                $name = base64_encode(trim($s3cloud_files_array[$i]));
                if (in_array($name, $files_db)) {
                    continue;
                }
                $table_name = $wpdb->prefix . "s3cloud_copy_files";
                $r = $wpdb->get_var(
                    $wpdb->prepare(
                        "
          SELECT name FROM `$table_name` WHERE name = %s LIMIT 1",
                        $name
                    )
                );
                if (!empty($r) or empty($name)) {
                    continue;
                }

                if ($ctd > $maxtomake) {
                    break;
                }

                $ctd++;

                $query = "insert IGNORE into `$table_name` (`name`, `splited`) VALUES ('$name', '0')";
                $msg =
                    "Added to list (table) to transfer: " .
                    base64_decode(trim($name));
                ///// <<<<  s3cloud_record_debug($msg);

                $r = $wpdb->get_var($query);
            } // end Loop

            if ($s3cloud_quant_files - count($files_db) < $maxtomake) {
                $table_name = $wpdb->prefix . "s3cloud_copy";
                $query =
                    "update " . $table_name . " SET mystatus = 'transferring'";
                $r = $wpdb->query($query);
                // s3cloud_record_log("transferring");
                s3cloud_record_debug("transferring");
                die("Transferring files...");
            }
            $files_db = s3cloud_get_files_from_db();
            $done = round((count($files_db) / $s3cloud_quant_files) * 100);
            if ($done > 99) {
                $done = 100;
            }
            // Update pointer...
            $table_name = $wpdb->prefix . "s3cloud_copy";
            $r = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE  `$table_name`
         SET pointer = %s",
                    $i
                )
            );
        } else {
            $table_name = $wpdb->prefix . "s3cloud_copy";
            $r = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE  `$table_name`
         SET mystatus = %s",
                    "transferring"
                )
            );
            // s3cloud_record_log("transferring");
            s3cloud_record_debug("transferring");
            die("Transferring files - 0%");
        }
        die("Loading name of files to table - " . $done . "%");
    }
    ////////////////////// TRANSFERRING ///////////////////////////////
    $st = s3cloud_get_copy_status();
    // transferring
    if (substr($st, 0, 12) == "transferring") {
        if ($s3cloud_copy_speed == "very_slow") {
            $maxtransfer = 100;
        } elseif ($s3cloud_copy_speed == "slow") {
            $maxtransfer = 300;
        } elseif ($s3cloud_copy_speed == "fast") {
            $maxtransfer = 500;
        } elseif ($s3cloud_copy_speed == "very_fast") {
            $maxtransfer = 700;
        } else {
            $maxtransfer = 400;
        } // era 500
        // >>>>>>>>>>>>>> have multipart to complete? <<<<<<<<<<<<<<<<<<<
        if($server_cloud == 'server') {
            $files_splited_to_join = s3cloud_get_files_to_join();
            if (count($files_splited_to_join) > 0) {
                s3cloud_complete_multipart($files_splited_to_join);
            }
            $files_splited_to_copy = s3cloud_get_files_to_copy_splited();
            if (count($files_splited_to_copy) > 0) {
                s3cloud_transfer_splited($files_splited_to_copy);
            }
        }
        // >>>>>>>>>>>>>> end have multipart to complete? <<<<<<<<<<<<<<<<<<<
        $files_to_copy = s3cloud_get_files_to_copy($maxtransfer);
        $tomake = count($files_to_copy);
        $qfiles_to_copy = count($files_to_copy);

       // die(var_export($files_to_copy ));

        // end transfer?
        if ($qfiles_to_copy == 0) {
            // exit only here
            s3cloud_record_debug("Joining Files...");
            $table_name = $wpdb->prefix . "s3cloud_copy";
            $r = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE  `$table_name`
            SET mystatus = %s",
                    "joining"
                )
            );
            // s3cloud_record_log('End of Job');
            die("Joining Files...");
        }


        $s3cloud_time_limit = time() + 60;

        for ($i = 0; $i < $qfiles_to_copy; $i++) {
            $name_file = $files_to_copy[$i]["name"];
            $id = $files_to_copy[$i]["id"];
            $upload_id = $files_to_copy[$i]["upload_id"];
            $splited = $files_to_copy[$i]["splited"];
            $part_number = $files_to_copy[$i]["part_number"];

            // >>>>>>>>>>>>>>>>>>>>>>  From Cloud

            $r = s3cloud_make_transfer(
                $name_file,
                $id,
                $upload_id,
                $splited,
                $part_number
            );

            if ($r == "10") {
                // Just splited large file...
                die("Transferring large file: " . $name_file);
            }

            if ($r == "-2") {
                // big file....
                if (s3cloud_flag_file($files_to_copy[$i]["id"]) === false) {
                    $txt = "Fail Flag file (too  big): " . $name_file;
                    s3cloud_record_debug($txt);
                    die("Fail to transfer. File too big: " . $name_file);
                }

                $txt = "***** Fail Copy file (too  big): " . $name_file;
                s3cloud_record_debug($txt);
                die("Fail to transfer. File too big: " . $name_file);
            } elseif ($r == "-1") {
                // update....
                // if (s3cloud_flag_file($id_to_copy[$i]["id"]) === false) {
                if (s3cloud_flag_file($id) === false) {
                    $txt = "Fail Copy file: " . $name_file;
                    s3cloud_record_debug($txt);
                    die("Fail to flag file: " . $name_file . "   id: " . $id);
                }
            } else {
                if ($r == "1") {
                    s3cloud_flag_file($id, "");

                    $txt = "Transferred file: " . $name_file;
                    /////  <<<<<<<  s3cloud_record_debug($txt);

                    if (strrpos($name_file, ".s3cloudpart")) {
                        die($txt);
                    }
                }
                if (time() > $s3cloud_time_limit) {
                    $done = s3cloud_get_files_done();
                    $todo = s3cloud_get_total_db_files();

                    if ($todo == 0 or $done == 0) {
                        die("Transferring Files...");
                    }

                    $res = ($done / $todo) * 100;
                    $done = round($res, 0);
                    die("Transferred: " . $done . "%");
                    // die('Transferred: '.$name_file);
                }
            }
        } // end loop

        die("Transferring Files...");
    } // if (substr($st, 0, 12) == 'transferring')

    ////////////////////// JOINING ///////////////////////////////

    $st = s3cloud_get_copy_status();

    if (substr($st, 0, 7) == "joining") {
        $r = s3cloud_join();

        if ($r == "1") {
            $table_name = $wpdb->prefix . "s3cloud_copy";
            $r = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE  `$table_name`
            SET mystatus = %s",
                    "end"
                )
            );

            s3cloud_record_debug("End of Job");
            // s3cloud_record_log("End of Job");
            die("End of Job!");
        }

        die("Joining  Files!");
    }
} // end main function

///////// END   ////////////////////////////////////////////////////

function s3cloud_make_transfer(
    $s3cloud_name_file,
    $id_to_copy,
    $upload_id,
    $splited,
    $part_number
) {
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $s3cloud_time_limit;
    global $wpdb;
    global $s3cloud_s3;
    $s3cloud_time_limit = time() + 60;
    $table_name = $wpdb->prefix . "s3cloud_copy_files";
    $path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
    require_once $path;
    if (empty($s3cloud_name_file)) {
        return "-1";
    }

    //  ------------  PREPAR server to cloud

    if ($server_cloud == "server") {
        $pos = strrpos($s3cloud_name_file, "/");
        $file_name_base = substr($s3cloud_name_file, $pos + 1);

        // /home/s3cloud/public_html/lixo/teste2/scan_log.php
        if (substr($folder_cloud, 0, 4) == "Root") {
            $folder_cloud = substr($folder_cloud, 5);
        }
        $pos = strrpos($folder_server, "/");
        $capar = substr($folder_server, 0, $pos + 1);
        if (empty($folder_cloud)) {
            $s3cloudkey = str_replace($capar, "", $s3cloud_name_file);
        } else {
            //die($folder_cloud);
            $s3cloudkey =
                $folder_cloud .
                "/" .
                str_replace($capar, "", $s3cloud_name_file);
            $s3cloudkey = str_replace("//", "/", $s3cloudkey);
        }

        // --------------- MAKE COPY.... server to Cloud

        try {
            if (!file_exists($s3cloud_name_file)) {
                // >>>>>>>>>>>>>>>>>>>>>>>>>>>>> s3cloud_flag_file($id_to_copy[$i]["id"]);
                $msg = "File doesn't exist  " . $s3cloud_name_file;
                s3cloud_record_debug($msg);
                return "-1";
            }
            // bigger than  3 000 000 3 mega
            if (
                filesize($s3cloud_name_file) > 3000000 and
                time() + 30 > $s3cloud_time_limit
            ) {
                $done = s3cloud_get_files_done();
                $todo = s3cloud_get_total_db_files();
                $res = ($done / $todo) * 100;
                $done = round($res, 0);
                die("Transferred::: " . $done . "%");
            }
            if (filesize($s3cloud_name_file) > 1200000000) {
                // 165
                return "-2";
            }

            // Limit size....

        } catch (Exception $exception) {
            $msg =
                "Failed to Read Files From Server with error: " .
                $exception->getMessage();
            s3cloud_record_debug($msg);
            return "-1";
        }

        /* ============  Split Large files Server =============== */
        // split large
        if (filesize($s3cloud_name_file) > 100000000) {
            // Split files
            $s3cloud_f_split = s3cloud_split_server_file(
                $s3cloud_name_file,
                $id_to_copy
            );
            $table_name = $wpdb->prefix . "s3cloud_copy_files";

            // INSERT CREATED SPLITTED ON TABLE
            for ($q = 0; $q < count($s3cloud_f_split); $q++) {
                $name = $s3cloud_f_split[$q];
                if (empty($name)) {
                    continue;
                }

                $wname = base64_encode(trim($name));

                $wq = $q + 1;

                $wupload_id = base64_encode($upload_id);

                $query = "insert IGNORE into `$table_name` (`name`, `splited` , `upload_id` , `part_number`) VALUES ('$wname', '1', '$wupload_id', '$wq')";

                $msg = "splited: " . $query;
                // s3cloud_record_debug($msg);
                $r = $wpdb->get_results($query);


            } // end loop insert on table

            // done...

            if ($q > 0) {
                $query = "update `$table_name` set `flag` = '1' WHERE id = '$id_to_copy' LIMIT 1";
                $msg = "splited: " . $query;
                //s3cloud_record_debug($msg);
                $r = $wpdb->get_results($query);
                // error_log($query);
                // die($query);
            }

            return "10";
            // } // END filesize($name_file) > 105000000
        } else {
            // No split ...

            if($splited == '1')
              return '1';

            try {
                $r = $s3cloud_s3->putObject([
                    "Bucket" => $bucket_name,
                    "Key" => $s3cloudkey,
                    "SourceFile" => $s3cloud_name_file,
                ]);

                if ($r !== false) {
                    $objInfo = $s3cloud_s3->doesObjectExist(
                        $bucket_name,
                        $s3cloudkey
                    );
                    if (!$objInfo) {
                        $msg = "Failed to tranfer:  " . $s3cloudkey;
                        s3cloud_record_debug($msg);
                        die("FAIL TO TRANSFER SERVER TO CLOUD");
                    }
                    return "1";
                } else {
                    $msg = "Failed to tranfer:  " . $s3cloudkey;
                    s3cloud_record_debug($msg);
                    die("FAIL TO TRANSFER SERVER TO CLOUD");
                    return "-1";
                }
            } catch (Exception $exception) {
                $msg =
                    "Failed to transfer: $file_name_base , with error: " .
                    $exception->getMessage();
                s3cloud_record_debug($msg);
                //error_log($msg);
                // die("Failed to transfer ". $file_name_base);  // with error: " . $exception->getMessage();
                return "-1";
            }
        }
        // END MAKE COPY SERVER TO CLOUD
    } else {


        //  ///////////////////  transfer Cloud To Server //////////////////////


        $s3cloudkey = $s3cloud_name_file;
        $pos = strrpos($s3cloud_name_file, "/");
        $folder_server2 = substr($s3cloud_name_file, 0, $pos) . "/";
        if ($pos !== false) {
            $s3cloud_name_base = substr($s3cloud_name_file, $pos + 1);
        } else {
            $s3cloud_name_base = $s3cloud_name_file;
        }
        $s3cloud_temp = explode("/", $folder_server2);
        $s3cloud_filepath = $folder_server . "/" . $folder_server2;
        $s3cloud_temp2 = $folder_server;
        for ($w = 0; $w < count($s3cloud_temp); $w++) {
            $s3cloud_temp2 .= "/" . $s3cloud_temp[$w];
            if (!is_dir($s3cloud_temp2)) {
                if (!mkdir($s3cloud_temp2, 0755, true)) {
                    $msg = "Failed to create folder: " . $s3cloud_temp2;
                    s3cloud_record_debug($msg);
                    die("Failed to create directories...");
                }
            }
        }
        $s3cloud_server_filepath = $folder_server . "/" . $folder_server2;
        if (!is_dir($s3cloud_server_filepath)) {
            if (!mkdir($s3cloud_server_filepath, 0755, true)) {
                $msg = "Failed to create folder: " . $s3cloud_server_filepath;
                s3cloud_record_debug($msg);
                die("Failed to create directories...");
            }
        }
        $s3cloud_filepath .= $s3cloud_name_base;
        $s3cloud_filepath = str_replace("//", "/", $s3cloud_filepath);
        try {
            $objInfo9 = $s3cloud_s3->doesObjectExist($bucket_name, $s3cloudkey);
            if (!$objInfo9) {
                $msg = "Object (File) doesn't exist  " . $s3cloudkey;
                s3cloud_record_debug($msg);
                return "-1";
            }
            $objInfo = $s3cloud_s3->headObject([
                "Bucket" => $bucket_name,
                "Key" => $s3cloudkey,
            ]);
            // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
            if ($objInfo["ContentLength"] > 1200000000) {
                return "-2";
            }
        } catch (Exception $exception) {
            $msg =
                "Failed to Read From Cloud: " .
                $s3cloudkey .
                " with error: " .
                $exception->getMessage();
            s3cloud_record_debug($msg);
            return "-1";
        }
        try {
            // bigger than 3 mega???
            if (
                $objInfo["ContentLength"] > 3000000 and
                time() + 20 > $s3cloud_time_limit
            ) {
                $done = s3cloud_get_files_done();
                $todo = s3cloud_get_total_db_files();
                $res = ($done / $todo) * 100;
                $done = round($res, 0);
                die("Transferred: " . $done . "%");
            }
        } catch (Exception $exception) {
            $msg =
                "Failed to Read Stream: " .
                $s3cloudkey .
                " with error: " .
                $exception->getMessage();
            s3cloud_record_debug($msg);
            return "-1";
        }
        if ($objInfo["ContentLength"] > 100000000) {
            $s3cloud_filesize = $objInfo["ContentLength"];

            $s3cloud_done = 0;
            $s3cloud_start_byte = 0;
            $s3cloud_buffer = 20000 * 1024;
            $s3cloud_end_byte = $s3cloud_buffer - 1;
            $part = 0;

            // >>>>>>>>>>  erase all possible temp?????


            while ($s3cloud_done < $s3cloud_filesize) {

                // gc_collect_cycles();

                $time_start = microtime(true);
                try {
                    $file_part_path = $s3cloudkey . ".s3cloudpart" . $part;
                    $objInfo2 = $s3cloud_s3->doesObjectExist(
                        $bucket_name,
                        $file_part_path
                    );
                    if (!$objInfo2) {
                        $todo = $s3cloud_filesize - $s3cloud_done;
                        if ($s3cloud_buffer > $todo) {
                            $s3cloud_buffer = $todo;
                        }

                        $s3cloud_Range =
                            "bytes=" .
                            (string) $s3cloud_start_byte .
                            "-" .
                            (string) $s3cloud_end_byte;
                        $file = $s3cloud_s3->getObject([
                            "Bucket" => $bucket_name,
                            "Key" => $s3cloudkey,
                            "Range" => $s3cloud_Range,
                        ]);

                        $s3cloud_temp = $file["Body"];
                        $s3cloud_temp_size = strlen($s3cloud_temp);

                        if ($s3cloud_temp_size != $s3cloud_buffer) {
                            die("Fail to split: " . $part);
                        }
                    } else {
                        // exists...
                        $objInfo4 = $s3cloud_s3->headObject([
                            "Bucket" => $bucket_name,
                            "Key" => $file_part_path,
                        ]);

                        if (gettype($objInfo4) == "object") {
                            $s3cloud_temp_size = $objInfo4["ContentLength"];
                        } else {
                            die("Fail to split (759) Part: " . $part);
                        }
                    }
                    $s3cloud_done = $s3cloud_done + $s3cloud_temp_size;
                    $s3cloud_start_byte = $s3cloud_done;
                    $todo = $s3cloud_filesize - $s3cloud_done;
                    if ($todo > $s3cloud_buffer) {
                        $todo = $s3cloud_buffer;
                    }
                    $s3cloud_end_byte = $s3cloud_start_byte + $todo - 1;
                    $time_end = microtime(true);
                    $time = $time_end - $time_start;
                    $msg = "duration read obj " . $time;
                    // s3cloud_record_debug($msg);
                    $wname = $s3cloudkey . ".s3cloudpart" . $part;
                    if (!$objInfo2) {
                        if (empty($s3cloud_temp)) {
                            // debug...
                            die(
                                "empty (1). offset: " .
                                    $s3cloudkey .
                                    " offset  " .
                                    $s3cloud_start_byte
                            );
                        }

                        $st = s3cloud_get_copy_status();
                        if (substr($st, 0, 12) != "transferring") {
                            die("cancelled.");
                        }

                        $time_start = microtime(true);
                        $r = $s3cloud_s3->putObject([
                            "Bucket" => $bucket_name,
                            "Key" => $file_part_path,
                            "Body" => $s3cloud_temp,
                        ]);

                        $st = s3cloud_get_copy_status();
                        if (substr($st, 0, 12) != "transferring") {
                            $result = $s3cloud_s3->deleteObject([
                                "Bucket" => $bucket_name,
                                "Key" => $file_part_path,
                            ]);

                            die("cancelled.");
                        }
                    }
                    $s3cloud_temp = null;
                } catch (Exception $exception) {
                    $msg =
                        "Failed to Get Object with Range: " .
                        $s3cloudkey .
                        " with error: " .
                        $exception->getMessage();
                    s3cloud_record_debug($msg);
                    return "-1";
                }
                $s3cloud_temp = null;
                $time_end = microtime(true);
                $time = $time_end - $time_start;
                $msg = "duration write obj " . $time;
                // s3cloud_record_debug($msg);
                $table_name = $wpdb->prefix . "s3cloud_copy_files";
                $wname = base64_encode(trim($wname));
                $query = "select name from $table_name WHERE name = '$wname' limit 1";
                $r = $wpdb->get_var($query);

                if (empty($r) or $r == "NULL") {
                    $query = "insert IGNORE into `$table_name` (`name`, `splited`) VALUES ('$wname', '1')";
                
                }    else {

                    $query = "UPDATE `$table_name` set `splited` = '1', flag = '' WHERE name = '$wname' limit 1";
 
                }
                
                    $msg = "spl " . $query;
                    $msg .= PHP_EOL;
                    $msg .= "Name File: " . base64_decode($wname);
                    // s3cloud_record_debug($msg);
                    $r = $wpdb->get_var($query);
                $part++;
                gc_collect_cycles();

                if (time() + 30 > $s3cloud_time_limit) {
                    die("Splitting large file: " . $s3cloudkey);
                }
            } // Loop
            s3cloud_flag_file($id_to_copy, "1");
            die("Spliting large file: " . $s3cloudkey);
        } // if $objInfo['ContentLength'] > 105000000 )
        try {
            $result = $s3cloud_s3->getObject([
                "Bucket" => $bucket_name,
                "Key" => $s3cloudkey, // Object Key
                "SaveAs" => $s3cloud_filepath,
            ]);
            if ($result) {
                return "1";
            } else {
                $msg = "Failed to transfer: " . $s3cloud_filepath . " (-99)";
                s3cloud_record_debug($msg);
                return "-1";
            }
        } catch (Exception $exception) {
            $msg =
                "Failed to transfer $file_name_base with error: " .
                $exception->getMessage();
            s3cloud_record_debug($msg);
            return "-1";
        } // end catch
    } // end cloud-server
    return false;
} // end function make copy

function s3cloud_get_copy_status()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "s3cloud_copy";
    if (!s3cloud_tablexist($table_name)) {
        return;
    }

    $query = "select mystatus from $table_name ORDER BY id DESC limit 1";
    return $wpdb->get_var($query);
}

function s3cloud_record_log($text)
{
    global $wpdb;

    $table_name = $wpdb->prefix . "s3cloud_copy";
    if (!s3cloud_tablexist($table_name)) {
        return;
    }

    $txt = PHP_EOL . date("Y-m-d H:i:s") . " " . $text . PHP_EOL;
    $txt .= "------------------------------";
    $query = "select log from $table_name ORDER BY id DESC limit 1";
    $log = $wpdb->get_var($query);
    $content = $log . $txt;
    $r = $wpdb->query(
        $wpdb->prepare(
            "UPDATE  `$table_name`
     SET log = %s",
            $content
        )
    );
}

function s3cloud_get_qfiles()
{
    global $wpdb;
    global $bill_debug;
    if ($bill_debug) {
        return 500;
    }
    $table_name = $wpdb->prefix . "s3cloud_copy";
    $query = "select qfiles from $table_name ORDER BY id DESC limit 1";
    return $wpdb->get_var($query);
}

function s3cloud_fetch_files($dir, &$results = [])
{
    try {
        $files = scandir($dir);
    } catch (Exception $exception) {
        $msg = "Failed to scandir with error: " . $exception->getMessage();
        s3cloud_record_debug($msg);
        die("Fail to Scandir");
    } // end catch

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (is_dir($path) == false) {
            $results[] = $path;
        } elseif ($value != "." && $value != "..") {
            s3cloud_fetch_files($path, $results);
            if (is_dir($path) == false) {
                $results[] = $path;
            }
        }
    }
    return $results;
}
function s3cloud_get_files_from_db()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "s3cloud_copy_files";
    $query =
        "select name, id from " .
        $table_name .
        " where flag <> '1' ORDER BY id"; //  LIMIT 1000";
    $query = "select name, id from " . $table_name . " ORDER BY id"; //  LIMIT 1000";
    $results = $wpdb->get_results($query, ARRAY_A);
    return $results;
}

function s3cloud_get_files_to_join()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "s3cloud_copy_files";
    $query =
        "select name, id, upload_id, splited, part_number, etag from " .
        $table_name .
        " where flag = '9'";
    $r = $wpdb->get_results($query, ARRAY_A);


    if (count($r) > 0) {
        for ($i = 0; $i < count($r); $i++) {
            $r[$i]["name"] = base64_decode($r[$i]["name"]);
            $r[$i]["upload_id"] = base64_decode($r[$i]["upload_id"]);
            $r[$i]["etag"] = base64_decode($r[$i]["etag"]);
        }

        //sort...
        usort($r, function ($a, $b) {
            return strnatcasecmp($a["name"], $b["name"]);
        });
    }

    return $r;
}

function s3cloud_get_files_to_copy_splited()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "s3cloud_copy_files";
    $query =
        "select name, id, flag, upload_id, splited, part_number from " .
        $table_name .
        " where splited = '1'";
    $r = $wpdb->get_results($query, ARRAY_A);

    //die($query );


    if (count($r) > 0) {
        for ($i = 0; $i < count($r); $i++) {
            $r[$i]["name"] = base64_decode($r[$i]["name"]);
            $r[$i]["upload_id"] = base64_decode($r[$i]["upload_id"]);
        }

        //sort...
        usort($r, function ($a, $b) {
            return strnatcasecmp($a["name"], $b["name"]);
        });
    }

    return $r;
}
function s3cloud_get_files_to_copy($limit)
{
    global $wpdb;

    $table_name = $wpdb->prefix . "s3cloud_copy_files";
    $query =
        "select name, id, upload_id, splited, part_number from " .
        $table_name .
        " where flag <> '1' and flag <> '2' and flag <> '9' LIMIT " .
        $limit;
    $r = $wpdb->get_results($query, ARRAY_A);

    for ($i = 0; $i < count($r); $i++) {
        $r[$i]["name"] = base64_decode($r[$i]["name"]);
        $r[$i]["upload_id"] = base64_decode($r[$i]["upload_id"]);
    }

    //sort...
    usort($r, function ($a, $b) {
        return strnatcasecmp($a["name"], $b["name"]);
    });

    return $r;
}

function s3cloud_get_files_done()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "s3cloud_copy_files";
    // $query = "select name, id from " . $table_name . " where flag <> '1' ORDER BY id LIMIT " . $limit;
    $query = "select count(*) from $table_name where flag = '1'";
    return $wpdb->get_var($query);
}
function s3cloud_get_total_db_files()
{
    global $wpdb;
    global $bill_debug;
    if ($bill_debug) {
        return 500;
    }
    $table_name = $wpdb->prefix . "s3cloud_copy_files";
    $query = "select count(*) from $table_name";
    return $wpdb->get_var($query);
}
function s3cloud_unflag()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "s3cloud_copy_files";
    $query = "update " . $table_name . " SET flag = ''";
    $r = $wpdb->query($query);
    return $r;
}
function s3cloud_flag_file($id, $splited = "")
{
    global $wpdb;
    $table_name = $wpdb->prefix . "s3cloud_copy_files";

    // update wp_s3cloud_copy_files SET flag = '1' WHERE id = 1 LIMIT 1


    if ($splited == "") {
        $query =
            "update " . $table_name .
            " SET flag = '1'
      WHERE id = $id LIMIT 1";
      //die($query);
        $r = $wpdb->query($query);
    } else {
        $query =
            "update " . $table_name .  
             " SET flag = '1', splited = '$splited'
      WHERE id = '$id'
      LIMIT 1";
      //die($query);
        $r = $wpdb->query($query);
    }

    return $r;
}
function s3cloud_copy_inic()
{
    global $wpdb;
    global $server_cloud;
    global $folder_server;
    global $folder_cloud;
    global $bucket_name;

    $table_name = $wpdb->prefix . "s3cloud_copy";
    if (s3cloud_tablexist($table_name)) {

        $query = "TRUNCATE TABLE " . $table_name;

        $r = $wpdb->query($query);

        $r = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO `$table_name` 
        (`from`, `folder_server`, `folder_cloud`, `bucket`,`mystatus`)
        VALUES (%s, %s , %s ,%s,'starting')",
                $server_cloud,
                $folder_server,
                $folder_cloud,
                $bucket_name
            )
        );
    }

    $table_name = $wpdb->prefix . "s3cloud_copy_files";
    if (s3cloud_tablexist($table_name)) {
        $query = "TRUNCATE TABLE " . $table_name;
        $r = $wpdb->query($query);
        s3cloud_unflag();
    }
}
function s3cloud_get_scan_status()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "s3cloud_scan";
    $query = "select mystatus from $table_name ORDER BY id DESC limit 1";
    return $wpdb->get_var($query);
}

function s3cloud_scan_cloud($folder_cloud)
{
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $s3cloud_s3;

    if (empty($folder_cloud)) {
        return false;
    }

    $path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
    require_once $path;

    try {
        if (substr($folder_cloud, 0, 4) == "Root") {
            $folder_cloud = "";
        }

        $objects = $s3cloud_s3->getIterator("ListObjects", [
            "Bucket" => $bucket_name,
            "Prefix" => $folder_cloud,
        ]);
    } catch (AWSException $e) {
        $msg =
            "Failed to Get List of objects with error: " .
            $exception->getMessage();
        s3cloud_record_debug($msg);
        die();
    }
    $files = [];

    foreach ($objects as $ob) {
        if (substr($ob["Key"], -1) != "/") {
            $files[] = $ob["Key"];
        }
    }

    return $files;
} // end function scan cloud

function s3cloud_split_server_file($s3cloud_name_file, $id)
{
    //  ------------  PREPAR server to split

    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $s3cloud_time_limit;
    global $s3cloud_s3;

    if (empty($s3cloud_name_file)) {
        return false;
    }

    $pos = strrpos($s3cloud_name_file, "/");
    $file_name_base = substr($s3cloud_name_file, $pos + 1);

    $file_part_path = substr($s3cloud_name_file, 0, $pos);

    if (substr($folder_cloud, 0, 4) == "Root") {
        $folder_cloud = substr($folder_cloud, 5);
    }

    $pos = strrpos($folder_server, "/");
    $capar = substr($folder_server, 0, $pos + 1);

    if (empty($folder_cloud)) {
        $s3cloudkey = str_replace($capar, "", $s3cloud_name_file);
    } else {
        $s3cloudkey =
            $folder_cloud . "/" . str_replace($capar, "", $s3cloud_name_file);
    }

    // --------------- SPLIT .... server

    try {
        $s3cloud_buffer = 20000 * 1024;

        //open file to read
        $file_handle = fopen($s3cloud_name_file, "r");
        if ($file_handle === false) {
            $msg = "Fail Open File: " . $s3cloud_name_file;
            s3cloud_record_debug($msg);
            die($msg);
        }

        //get file size
        $file_size = filesize($s3cloud_name_file);
        //no of parts to split
        $parts = $file_size / $s3cloud_buffer;

        //store all the file names
        $file_parts = [];

        //path to write the final files
        //$store_path = getcwd(); // "splits/";
        $store_path = $folder_server . "/";

        //name of input file
        $file_name = basename($s3cloud_name_file);

        for ($i = 0; $i < $parts; $i++) {
            $st = s3cloud_get_copy_status();
            if (substr($st, 0, 12) != "transferring") {
                die("cancelled.");
            }

            //read buffer sized amount from file
            $file_part = fread($file_handle, $s3cloud_buffer);
            //the filename of the part

            $file_part_name =
                $file_part_path . "/" . $file_name . ".s3cloudpart$i";

            //open the new file [create it] to write
            $file_new = fopen($file_part_name, "w+");
            if($file_new === false) {
                $msg = "Fail to Open Temporary File: " . $file_part_name;
                s3cloud_record_debug($msg);
                die($msg);
            }


            //write the part of file
            $r = fwrite($file_new, $file_part);
            if($r === false) {
                $msg = "Fail to Write Temporary File: " . $file_part_name;
                s3cloud_record_debug($msg);
                die($msg);
            }

            if(!file_exists($file_part_name))
            {

                $msg = "Fail to Create Temporary File: " . $file_part_name;
                s3cloud_record_debug($msg);
                die($msg);


            }
            

            array_push($file_parts, $file_part_name);
            //die('array: '.var_export($file_parts,true));

            //close the part file handle
            fclose($file_new);
        }
        //close the main file handle
        fclose($file_handle);

        return $file_parts;

    } catch (Exception $exception) {
        $msg =
            "Failed to split $s3cloud_name_file with error: " .
            $exception->getMessage();
        s3cloud_record_debug($msg);
        // return "-1";
        return [];
    }
} // end function s3cloud_split_server_file

function s3cloud_join()
{
    global $wpdb;
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $s3cloud_time_limit;
    global $s3cloud_s3;
    $path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
    require_once $path;
    // $msg = "Begin function";


    $table_name = $wpdb->prefix . "s3cloud_copy_files";
   
    if($server_cloud == 'cloud') {
        $query =
        "select name, id, upload_id, etag, part_number from " .
        $table_name .
        " where flag = '1' and splited = '1'";
    }
    else {

        $query =
            "select name, id, upload_id, etag, part_number from " .
            $table_name .
            " where (flag = '2' and splited = '0') or (flag = '1' and splited = '1')";

    }


    //    " where flag = '1' and splited = '1'";
    $r = $wpdb->get_results($query, ARRAY_A);
    if ($r === false) {
        die("Fail to read table (to join)");
    }
    if (count($r) < 1) {
        // end of job ...
        return "1";
    }


    for ($i = 0; $i < count($r); $i++) {
        $r[$i]["id"] = $r[$i]["id"];
        $r[$i]["part_number"] = $r[$i]["part_number"];
        $r[$i]["upload_id"] = base64_decode($r[$i]["upload_id"]);
        $r[$i]["etag"] = base64_decode($r[$i]["etag"]);
        $r[$i]["name"] = base64_decode($r[$i]["name"]);
    }

    // No parts...
    for ($w = 0; $w < count($r); $w++) {
        if (strpos($r[$w]["name"], ".s3cloudpart") !== false) {
            break;
        }
    }


    if (count($r) == $w) {
        return "1";
    }

    //sort...
    usort($r, function ($a, $b) {
        return strnatcasecmp($a["name"], $b["name"]);
    });

    for ($i = 0; $i < count($r); $i++) {
        $id = $r[$i]["id"];
        $name = $r[$i]["name"];
        $etag = $r[$i]["etag"];
        $upload_id = $r[$i]["upload_id"];

        $pos2 = strrpos($name, "/");
        $filepath = trim(substr($name, 0, $pos2 + 1));
        if ($server_cloud == "cloud") {
            if ($pos2 === false) {
                $filepath = "";
                $namefile = $name;
            } else {
                $filepath = trim(substr($name, 0, $pos2 + 1));
                $namefile = trim(substr($name, $pos2));
            }
        } else {
            $namefile = trim(substr($name, $pos2 + 1));
        }
        $pos = strrpos($namefile, ".");
        if ($pos === false) {
            $part = "";
        } else {
            $part = trim(substr($namefile, $pos + 1));
        }
        if ($part == ".s3cloudpart") {
            $original_name = trim(substr($namefile, 0, $pos));
        } else {
            $original_name = $namefile;
        }
        $newarray[$i]["originalname"] = $original_name;
        $newarray[$i]["filepath"] = $filepath;
        $newarray[$i]["namefile"] = $namefile;
        $newarray[$i]["id"] = $id;
        $newarray[$i]["etag"] = $etag;
        $newarray[$i]["upload_id"] = $upload_id;

    } // end loop

    // filter
    $s3cloud_original_name = $newarray[0]["originalname"];
    $newarray_todo = $newarray;
    for ($j = 0; $j < count($newarray); $j++) {
        if ($newarray[$j]["originalname"] == $s3cloud_original_name) {
            $newarray_todo[$j] = $newarray[$j];
        }
    }

    $newarray = $newarray_todo;

    //sort...
    usort($newarray, function ($a, $b) {
        return strnatcasecmp($a["namefile"], $b["namefile"]);
    });


    for ($i = 0; $i < count($newarray); $i++) {
        if (!isset($newarray[$i])) {
            continue;
        }


        // >>>>>>>>>>>>>>>>>>>>>>>>  do it...

        if ($server_cloud == "cloud") {


            // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>    Cloud to ==>  Server




            $original_name = $newarray[$i]["originalname"];

            // die(var_export($original_name));


            $file_path = $newarray[$i]["filepath"];
            if (empty($original_name)) {
                continue;
            }




            $pos = strpos($original_name, ".s3cloudpart");
            if ($pos === false) {
                continue;
            }
            $filetemp = $folder_server . "/" . $original_name;
            // >>>>>>>>>>>>>>>>>> Get Original
            // double check
            $pos = strrpos($original_name, ".s3cloudpart");
            if ($pos === false) {
                $s3cloudkey_original = $original_name;
            } else {
                $s3cloudkey_original = substr($original_name, 0, $pos);
            }
            // end double check original
            $filepath = trim($file_path);
            if (empty($filepath)) {
                $filetempori = $folder_server . "/" . $s3cloudkey_original;
            } else {
                $filetempori =
                    $folder_server . "/" . $filepath . $s3cloudkey_original;
            }
            $filetempori = str_replace("//", "/", $filetempori);
            // Begin low level
            try {
                if (file_exists($filetempori)) {
                    // die(var_export(__LINE__));
                    $fh_original = fopen($filetempori, "a");
                    fseek($fh_original, SEEK_END);
                } else {
                    // die(var_export(__LINE__));
                    $fh_original = fopen($filetempori, "w");
                }
                if ($fh_original === false) {
                    $msg = "Failed to Create / Open file: " . $filetemp;
                    s3cloud_record_debug($msg);
                    die($msg);
                } else {
                }
            } catch (Exception $exception) {
                $msg =
                    "Failed to Open: $filetemp , with error: " .
                    $exception->getMessage();
                s3cloud_record_debug($msg);
                die("Fail to Create Temp File " . $filetemp);
                //return "-1";
            }

            // Loop

            for ($j = 0; $j < count($newarray); $j++) {
                if (empty($newarray[$j]["originalname"])) {
                    continue;
                }
                $file_part_name = $newarray[$j]["namefile"];
                $id = $newarray[$j]["id"];
                $filepath = trim($newarray[$j]["filepath"]);
                if (!empty($filepath)) {
                    $filetemp =
                        $folder_server . "/" . $filepath . $original_name;
                } else {
                    $filetemp = $folder_server . "/" . $original_name;
                }
                while (strpos($filetemp, "//") !== false) {
                    $filetemp = str_replace("//", "/", $filetemp);
                }
                try {



                    // Part0, truncate original.
                    if (strpos($filetemp, "s3cloudpart0") !== false) {
                        ftruncate($fh_original, 0);
                    }
                    if (!file_exists($filetemp)) {
                        $msg = "File Part name doesn't exist: " . $filetemp;
                        s3cloud_record_debug($msg);
                        die($msg);
                    } else {
                        $fh_part = fopen($filetemp, "r");
                        if ($fh_part === false) {
                            $msg = "Failed to Open Part Name: " . $filetemp;
                            s3cloud_record_debug($msg);
                            die($msg);
                        }
                                      }



                    if ($s3cloudkey_original != $file_part_name) {

                        $file_part_size = filesize($filetemp);
                        $file_part = fread($fh_part, $file_part_size);
                        fclose($fh_part);



                        if (substr($folder_cloud, 0, 4) == "Root") {
                            $folder_cloud = $file_path.substr($folder_cloud, 5);
                        }


                        if (empty($folder_cloud)) {
                            $s3cloudkey = $file_part_name;
                        } else {
                            $s3cloudkey = $folder_cloud . "/" . $file_part_name;
                        }
                        while (strpos($s3cloudkey, "//") !== false) {
                            $s3cloudkey = str_replace("//", "/", $s3cloudkey);
                        }



                        $r = fwrite($fh_original, $file_part);

                        if ($r != $file_part_size) {
                            $msg =
                                "Failed to join (-1524) file Name: " .
                                $filetemp;
                            s3cloud_record_debug($msg);
                            die("Error size...");
                        } else {



                            $pos = strpos($s3cloudkey, ".s3cloudpart");
                            if ($pos !== false) {
                                if (!unlink($filetemp)) {
                                    $msg =
                                        "Failed to erase temp file Name: " .
                                        $filetemp;
                                    s3cloud_record_debug($msg);
                                    die($msg);
                                }


                                if (substr(trim($s3cloudkey), 0, 1) == "/") {
                                    $s3cloudkey = substr($s3cloudkey, 1);
                                }

                                $result = $s3cloud_s3->deleteObject([
                                    "Bucket" => $bucket_name,
                                    "Key" => $s3cloudkey,
                                ]);


                                if ($result == false) {
                                    $msg =
                                        "Failed to erase temp file Name: " .
                                        $s3cloudkey;
                                    s3cloud_record_debug($msg);
                                    die($msg);
                                }


                            }
                        }
                    }
                    // " where flag = '2' and splited = '0'";
                    $query =
                        "update " .
                        $table_name .
                        " SET flag = '8', splited = '8'
                            WHERE id = $id 
                            LIMIT 1";
                    $r = $wpdb->query($query);
                    $msg = "Joined Part: " . $filetemp;
                    //s3cloud_record_debug($msg);
                    die($msg);
                } catch (Exception $exception) {
                    $msg =
                        "Failed Low Level operation Joining files (1), with error: " .
                        $exception->getMessage();
                    s3cloud_record_debug($msg);
                    die($msg); // with error: " . $exception->getMessage();
                    // return "-1";
                }
            } // end for next do it
            fclose($fh_original);
        }
        // endif $server_cloud == 'cloud')
        else {



            // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>   Server to => Cloud  <<<<<<<<<<<<<<<<<<<<<<<





            $s3cloud_time_limit2 = time() + 90;

            $msg = "Begin Server to cloud";
            //error_log($msg);
            //s3cloud_record_debug($msg);
            if (empty($original_name)) {
                continue;
            }

            $file_path = $newarray[$i]["filepath"];
            $original_name = $newarray[$i]["originalname"];
            $s3cloud_name_file = $file_path . "/" . $original_name;
            while (strpos($s3cloud_name_file, "//") !== false) {
                $s3cloud_name_file = str_replace("//", "/", $s3cloud_name_file);
            }
            $pos = strrpos($s3cloud_name_file, "/");
            $file_name_base = substr($s3cloud_name_file, $pos + 1);
            if (substr($folder_cloud, 0, 4) == "Root") {
                $folder_cloud = substr($folder_cloud, 5);
            }
            $pos = strrpos($folder_server, "/");
            $capar = substr($folder_server, 0, $pos + 1);
            if (empty($folder_cloud)) {
                $s3cloudkey_original = str_replace(
                    $capar,
                    "",
                    $s3cloud_name_file
                );
            } else {
                $s3cloudkey_original =
                    $folder_cloud .
                    "/" .
                    str_replace($capar, "", $s3cloud_name_file);
            }
            while (strpos($s3cloudkey_original, "//") !== false) {
                $s3cloudkey_original = str_replace(
                    "//",
                    "/",
                    $s3cloudkey_original
                );
            }
            // Begin low level
            try {
                // >>>>>>>>>>>>>>>>>> Get Original
                // double check
                $pos = strrpos($s3cloudkey_original, ".s3cloudpart");
                if ($pos !== false) {
                    $s3cloudkey_original = trim(
                        substr($s3cloudkey_original, 0, $pos)
                    );
                }
            } catch (Exception $exception) {
                $msg =
                    "Failed to Open: $s3cloudkey_original , with error: " .
                    $exception->getMessage();
                s3cloud_record_debug($msg);
                die($msg);
                // return "-1";
            }

            $namefile = $newarray[$i]["namefile"];
            $id = $newarray[$i]["id"];

            // die($newarray[$i]["namefile"]);

            $pos99 = strrpos($namefile, ".s3cloudpart");

            $filetemp2 = $newarray[$i]["namefile"];
            $filepath3 = $newarray[$i]["filepath"];

            $filetemp4 = $filepath3 . $filetemp2;

            // protect original...
            $pos99 = strrpos($filetemp2, ".s3cloudpart");
            if ($pos99 === false) {
                continue;
            }

            try {
                if (file_exists($filetemp4)) {


                    if (!unlink($filetemp4)) {
                        $msg =
                            "Failed to erase temp file Name (2): " . $filetemp4;
                        s3cloud_record_debug($msg);
                        //die($msg);
                    } else {
                        $msg = "Erased temp file Name (3): " . $filetemp4;
                        // s3cloud_record_debug($msg);
                    }

                    $query =
                        "update " .
                        $table_name .
                        " SET flag = '8', splited = '8'  " .
                        " WHERE id = '" .
                        $id .
                        "' 
                    LIMIT 1";
                    $r = $wpdb->query($query);
                }
            } catch (Exception $exception) {
                $msg =
                    "Failed Low Level operation Deleting Temporary Object (22), with error: " .
                    $exception->getMessage();
                s3cloud_record_debug($msg);
                fclose($s3cloud_stream);
                die("Failed to erase temp part (-4) " . $s3cloudkey); // with error: " . $exception->getMessage();
                return "-1";
            }

            // end...


            if (time() + 10 > $s3cloud_time_limit2) {
                die("Erasing Temp Files... " . $s3cloudkey);
            }

            continue;

            die("Joining files... " . $s3cloudkey);
        } // end Server or cloud
    } // end main

    die("Reloading...");
} // end function JOIN

function s3cloud_complete_multipart($files_to_join)
{
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $wpdb;
    $table_name = $wpdb->prefix . "s3cloud_copy_files";
   // ? is completed?
   $table_name = $wpdb->prefix . "s3cloud_copy_files";
   $query =
       "select name, id, upload_id, splited, part_number, etag from " .
       $table_name .
       " where splited = '1'";
   $r = $wpdb->get_results($query, ARRAY_A);
   $qtodo = count($r);
   $query =
   "select name, id, upload_id, splited, part_number, etag from " .
   $table_name .
   " where flag = '9'";
    $r = $wpdb->get_results($query, ARRAY_A);
    $qdone = count($r);
    if($qtodo > $qdone)
      return;
    $path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
    require_once $path;
        $i = 0;
        $s3cloud_name_file = $files_to_join[$i]["name"];
        $id = $files_to_join[$i]["id"];
        $upload_id = $files_to_join[$i]["upload_id"];
        $splited = $files_to_join[$i]["splited"];
        $part_number = $files_to_join[$i]["part_number"];
        if (empty($part_number)) {
            $part_number = 0;
        }
        $pos = strrpos($s3cloud_name_file, "/");
        $file_name_base = substr($s3cloud_name_file, $pos + 1);
        if (substr($folder_cloud, 0, 4) == "Root") {
            $folder_cloud = substr($folder_cloud, 5);
        }
        $pos = strrpos($folder_server, "/");
        $capar = substr($folder_server, 0, $pos + 1);
        if (empty($folder_cloud)) {
            $s3cloudkey = str_replace($capar, "", $s3cloud_name_file);
        } else {
            $s3cloudkey =
                $folder_cloud .
                "/" .
                str_replace($capar, "", $s3cloud_name_file);
            $s3cloudkey = str_replace("//", "/", $s3cloudkey);
        }
        $pos = strrpos($s3cloudkey, ".s3cloudpart");
        if ($pos !== false) {
            $s3cloudkey_original = substr($s3cloudkey, 0, $pos);
        }
        else
             $s3cloudkey_original = $s3cloudkey;
    for ($i = 0; $i < count($files_to_join); $i++) {
        $pIndex = $i + 1; // $partNumber - 1;
             $parts[$i] = [
                 "PartNumber" => $pIndex,
                 "ETag" => $files_to_join[$i]["etag"],
             ];
    }
    try {
        $result = $s3cloud_s3->completeMultipartUpload([
            "Bucket" => $bucket_name,
            "Key" => $s3cloudkey_original,
            "UploadId" => $upload_id,
            "MultipartUpload" => [
                "Parts" => $parts,
            ],
        ]);
    } catch (Exception $exception) {
        $msg =
            "Failed to complete multipart: " .
            $s3cloudkey_original .
            ", with error: " .
            $exception->getMessage();
        s3cloud_record_debug($msg);
        error_log($msg);
        die($msg);
    }
    if ($result) {
        for ($i = 0; $i < count($files_to_join); $i++) {
            $id = $files_to_join[$i]["id"];
            $query =
                "update " .
                $table_name .
                " SET flag = '2', splited = '0'  " .
                " WHERE id = '" .
                $id .
                "' 
                                    LIMIT 1";
            $r = $wpdb->query($query);
        }
    } else {
        die("fail to complete multipart 255");
    }
}

function s3cloud_transfer_splited($files_to_copy)
{
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $wpdb;
    $table_name = $wpdb->prefix . "s3cloud_copy_files";
    $path = S3CLOUDPATH . "/functions/s3cloud_connect.php";
    require_once $path;
    $qfiles_to_copy = count($files_to_copy);
    for ($i = 0; $i < $qfiles_to_copy; $i++) {
        $s3cloud_name_file = $files_to_copy[$i]["name"];
        $id = $files_to_copy[$i]["id"];
        $upload_id = $files_to_copy[$i]["upload_id"];
        $splited = $files_to_copy[$i]["splited"];
        $part_number = $files_to_copy[$i]["part_number"];
        $flag = $files_to_copy[$i]["flag"];
        if($flag == '9')
          continue;
        if (empty($part_number)) {
            $part_number = 0;
        }
        $pos = strrpos($s3cloud_name_file, "/");
        $file_name_base = substr($s3cloud_name_file, $pos + 1);
        //  die($s3cloud_name_file);
        // /home/s3cloud/public_html/lixo/teste2/scan_log.php
        if (substr($folder_cloud, 0, 4) == "Root") {
            $folder_cloud = substr($folder_cloud, 5);
        }
        $pos = strrpos($folder_server, "/");
        $capar = substr($folder_server, 0, $pos + 1);
        if (empty($folder_cloud)) {
            $s3cloudkey = str_replace($capar, "", $s3cloud_name_file);
        } else {
            $s3cloudkey =
                $folder_cloud .
                "/" .
                str_replace($capar, "", $s3cloud_name_file);
            $s3cloudkey = str_replace("//", "/", $s3cloudkey);
        }
        $pos = strrpos($s3cloudkey, ".s3cloudpart");
        if ($pos !== false) {
            $s3cloudkey_original = substr($s3cloudkey, 0, $pos);
        }
        else
             $s3cloudkey_original = $s3cloudkey;
        if ($part_number == '1' ) {
            $multipart_uploads = $s3cloud_s3->listMultipartUploads([
                "Bucket" => $bucket_name,
                //'Prefix' => (string) $job_object->job['s3dir'],
            ]);
            $uploads = $multipart_uploads["Uploads"];
            if (!empty($uploads)) {
                foreach ($uploads as $upload) {
                    $s3cloud_s3->abortMultipartUpload([
                        "Bucket" => $bucket_name,
                        "Key" => $upload["Key"],
                        "UploadId" => $upload["UploadId"],
                    ]);
                }
            }
        }
        try {
            $pos = strrpos($s3cloudkey, ".s3cloudpart");
            if ($pos !== false) {
                $s3cloudkey_original = substr($s3cloudkey, 0, $pos);
            }
            if (!isset($upload_id)) {
                $multipart_uploads = $s3cloud_s3->listMultipartUploads([
                    "Bucket" => $bucket_name,
                    //'Prefix' => (string) $job_object->job['s3dir'],
                ]);
                $uploads = $multipart_uploads["Uploads"];
                if (!empty($uploads)) {
                    $upload_id = $uploads[0]["UploadId"];
                } else {
                    // try again ...
                    die("Fail to start Multipart Uploads... (277)");
                }
            }
            $pos = strrpos($s3cloudkey, ".s3cloudpart");
            if ($pos !== false) {
                $s3cloudkey_original = substr($s3cloudkey, 0, $pos);
            }
            $multipart_uploads = $s3cloud_s3->listMultipartUploads([
                'Bucket' => $bucket_name
                //'Prefix' => (string) $job_object->job['s3dir'],
            ]);
            $uploads = $multipart_uploads['Uploads'];
           // die(var_export($uploads));
            if (!empty($uploads)) {
                $upload_id = $uploads[0]['UploadId'];
            }
            else
            {
                $result =  $s3cloud_s3->createMultipartUpload(array(
                    // 'ACL' => 'public-read',
                    'Bucket' => $bucket_name,
                    'Key' => $s3cloudkey_original,
                ));
                $upload_id = $result['UploadId'];
            }
            $result = $s3cloud_s3->uploadPart([
                "Bucket" => $bucket_name,
                "Key" => $s3cloudkey_original,
                "UploadId" => $upload_id,
                "PartNumber" => $part_number,
                "SourceFile" => $s3cloud_name_file,
            ]);
        } catch (Exception $e) {
            die("Upload Part error: " . $e->getMessage());
            //die($e->getLine());
            // $e->getFile(),
        }
        if ($result) {
            // update database $parts...
            $wetag = base64_encode($result["ETag"]);
            $wupload_id = base64_encode($upload_id);
            $query = "update `$table_name` set  `upload_id` = '$wupload_id', `part_number` = '$part_number', `etag` = '$wetag', `flag` = '9'   WHERE id = '$id' LIMIT 1";
            $msg = "uploaded splited query: " . $query;
            $r = $wpdb->get_results($query);
            die('Transferred Part: '.$part_number.'/'.$qfiles_to_copy);
            // continue;
        } else {
            if ($part_number == 0) {
                $s3cloud_s3->AbortMultipartUpload([
                    "Bucket" => $bucket_name,
                    "Key" => $s3cloudkey,
                    "UploadId" => $upload_id,
                ]);
            }
            $msg = "Failed to tranfer multipart:  " . $s3cloudkey;
            s3cloud_record_debug($msg);
            die($msg);
            return "-1";
        }
    } // end main loop
    if (!isset($s3cloudkey_original)) {
        $s3cloudkey_original = $s3cloud_name_file;
        $pos = strrpos($s3cloud_name_file, ".s3cloudpart");
        if ($pos !== false) {
            $s3cloudkey_original = substr($s3cloud_name_file, 0, $pos);
        }
    }
       die("Finished to upload large file: " . $s3cloudkey_original);
} // end function make transfer splited
