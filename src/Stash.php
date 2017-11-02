<?php

class Stash
{
    public static $stash_dir;
    const query_types = array('select', 'update', 'insert', 'delete');

    public static function getQuery($query_name, $directory, $original_text = FALSE)
    {
        if (in_array($directory, self::query_types))
        {
            if (isset(self::$stash_dir))
            {
                $file = self::$stash_dir.'/queries/'.$directory.'/'.$query_name.'.sql';

                if (file_exists($file))
                {
                    if ($original_text === FALSE)
                    {
                        $file_contents = preg_replace("/[\r\n]+/", " ", trim(file_get_contents($file)));
                    }
                    else
                    {
                        $file_contents = trim(file_get_contents($file));
                    }
                    
                    if (empty($file_contents))
                    {
                        die ($file.' is empty.');
                    }
                    
                    return $file_contents;
                }
                else
                {
                    die ($file.' doesn\'t exist.');
                }
            }
            else
            {
                die ('DB::define(\'stash_dir\', \'\') is not defined. Stash Query folder cannot be located.');
            }
        }
        else
        {
            die ('DB::TYPE is not valid.');
        }
    }
}
