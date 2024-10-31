<?php
if (!defined('ABSPATH')) {
    die('We\'re sorry, but you can not directly access this file.');
}
ini_set('memory_limit', '512M');
set_time_limit(600);
global $s3cloud_dir_for_search;

if (current_user_can('administrator')) {
    // The current user is an administrator
    //echo 'You are an administrator.';
} else {
    // The current user is not an administrator
    die('You are not an administrator. (13)');
}


$s3cloud_dir_for_search = getcwd() . '/';
// if (!defined('ABSPATH')) define('ABSPATH', '');
$s3cloud_dir_for_search = ABSPATH . '/';
$s3cloud_dir_for_search = ABSPATH ;
$s3cloud_data_filesys = s3cloud_fetch_files($s3cloud_dir_for_search);
// CREATE NODES //////////////////
$i = 0;
foreach ($s3cloud_data_filesys as $key => $item)
{
    if ($item['parent'] == '-1') continue;
    if (!isset($item['parent'])) die('fail L 88');
    $s3cloud_data_filesys[$item['parent']]['nodes'][] = $item['path'];
    continue;
}
//  CREATE JSON ////////////////////////
function s3cloud_create_json_filesys($indice_node, $s3cloud_data_filesys)
{
    global $s3cloud_json;
    //global $j;
    $columns = array_column($s3cloud_data_filesys, "path");
    array_multisort($columns, SORT_ASC, $s3cloud_data_filesys);
    $tot = count($s3cloud_data_filesys);
    $ctd = 0;
    $s3cloud_json = '[{"text":"Root","icon":"","nodes":[';
    for ($i = 0;$i < $tot;$i++)
    {
        $ctd++;
        if ($s3cloud_data_filesys[$i]['parent'] !== '-1') continue;
        $item = trim($s3cloud_data_filesys[$i]['path']);
        if (empty($item)) continue;
        $s3cloud_json .= '{';
        $s3cloud_json .= '"text":"';
        $s3cloud_json .= $item;
        $s3cloud_json .= '","icon":""';
        // extra nodes
        $indice_node = array_search($item, array_column($s3cloud_data_filesys, 'path') , true);
        if (isset($s3cloud_data_filesys[$indice_node]['nodes'])) $s3cloud_json = s3cloud_s3transfer_create_nodes($indice_node, $s3cloud_data_filesys);
        $s3cloud_json .= '}';
        if ($ctd < $tot - 0)
        {
            $s3cloud_json .= ',';
        }
    } // end for
    while (substr($s3cloud_json, -1) == ',')
    {
        $s3cloud_json = substr($s3cloud_json, 0, strlen($s3cloud_json) - 1);
    }
    $s3cloud_json .= ']'; // end node
    $s3cloud_json .= '}';
    // $s3cloud_json .= ']'; // end sub main node
    $s3cloud_json .= ']'; // end MAIN node
    return $s3cloud_json;
} // end function Create Json
if (!isset($indice_node)) $indice_node = '-1';
$s3cloud_json = s3cloud_create_json_filesys($indice_node, $s3cloud_data_filesys);
// $s3cloud_json='[{"text":"Inbox","icon":"","nodes":[{"text":"Office","icon":"fa fa-inbox","nodes":[{"icon":"fa fa-inbox","text":"Customers"},{"icon":"fa fa-inbox","text":"Co-Workers"}]},{"icon":"fa fa-inbox","text":"Others"}]},{"icon":"fa fa-archive","text":"Drafts"},{"icon":"fa fa-calendar","text":"Calendar"},{"icon":"fa fa-address-book","text":"Contacts"},{"icon":"fa fa-trash","text":"Deleted Items"}]';
$s3cloud_json = str_replace("<br>", "", $s3cloud_json);
$s3cloud_json = str_replace(array(
    "\n",
    "\r\n",
    "\r",
    "\t"
) , "", $s3cloud_json);
//die(esc_attr($s3cloud_json));
die($s3cloud_json);

function s3cloud_s3transfer_create_nodes($indice_node, $s3cloud_data_filesys)
{
    global $s3cloud_json;
    if (isset($s3cloud_data_filesys[$indice_node]['nodes']))
    {
        $s3cloud_data_filesys3 = $s3cloud_data_filesys[$indice_node]['nodes'];
        $tot3 = count($s3cloud_data_filesys3);
        if ($tot3 > 0) $s3cloud_json .= ',"nodes": [';
        for ($k = 0;$k < $tot3;$k++)
        {
            $item3 = trim($s3cloud_data_filesys3[$k]);
            $s3cloud_json .= ' {
            "icon": "",
            "text": "' . $item3 . '"';
            $indice_node_node = array_search($item3, array_column($s3cloud_data_filesys, 'path') , true);
            if (isset($s3cloud_data_filesys[$indice_node_node]['nodes']))
            {
                // Node has node
                $s3cloud_json = s3cloud_s3transfer_create_nodes($indice_node_node, $s3cloud_data_filesys);
            }
            $s3cloud_json .= '}';
            if ($k < $tot3 - 1)
            {
                $s3cloud_json .= ',';
            }
        } //  end for
        if ($tot3 > 0) $s3cloud_json .= ']';
    } // end if tem nodes
    return $s3cloud_json;
} // end function
function s3cloud_fetch_files($dir)
{
    global $s3cloud_filesys_result;
    global $s3cloud_dir_for_search;
    $i = 0;
    $x = scandir($dir);
    if (!isset($s3cloud_filesys_result)) $s3cloud_filesys_result = array();
    foreach ($x as $filename)
    {
        if ($filename == '.') continue;
        if ($filename == '..') continue;
        $filePath = $dir . $filename;
        if (!is_dir($filePath)) continue;
        if (empty($filePath)) continue;
        if (is_dir($filePath))
        {
            if ($i == 0)
            {
                // Novo parente.
                $parent = $dir;
                $parent_for_search = trim(substr($dir, 0, strlen($dir) - 1));
                if ($parent_for_search == substr($s3cloud_dir_for_search, 0, strlen($s3cloud_dir_for_search) - 1))
                {
                    $indice_parent = '-1';
                }
                else
                {
                    if (gettype(count($s3cloud_filesys_result)) == 'integer' and count($s3cloud_filesys_result) > 0)
                    {
                        $indice_parent = array_search($parent_for_search, array_column($s3cloud_filesys_result, 'path') , true);
                        if ($indice_parent === false)
                        {
                            // Bill
                            if (count($s3cloud_filesys_result) == 0) $indice_parent;
                            else die('NOT FOUND !!!!');
                        }
                        $indice_parent = array_search($parent_for_search, array_column($s3cloud_filesys_result, 'path') , true);
                    }
                    else
                    {
                        $indice_parent = 0;
                    }
                }
            } // end I = 0
            $ctd = count($s3cloud_filesys_result);
            $s3cloud_filesys_result[] = array(
                'path' => trim($filePath) ,
                'parent' => $indice_parent
            );
            $i++;
            $filePath = $dir . $filename . '/';
            foreach (s3cloud_fetch_files($filePath) as $childFilename)
            {
                if (gettype($childFilename) === 'object') continue;
                if (!isset($childFilename[0])) continue;
                if ($childFilename[0] == '.') continue;
                if ($childFilename[0] == '..') continue;
                $filePath2 = $dir . $childFilename[0];
                if (!is_dir($filePath2)) continue;
                if (empty($filePath2)) continue;
                $ctd = count($s3cloud_filesys_result);
                try
                {
                    $s3cloud_filesys_result[] = array(
                        'path' => trim($filePath2) ,
                        'parent' => '999'
                    );
                    $i++;
                }
                catch(Exception $e)
                {
                    echo 'Message: ' . esc_attr($e->getMessage());
                }
            }
        } // end isdir
    } // end for
    // die(var_export($s3cloud_filesys_result));
    return $s3cloud_filesys_result;
} // end function
