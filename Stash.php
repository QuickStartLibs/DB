<?php

class Stash
{
    public static $stash_dir;

    public static function getQuery($query_name, $directory)
    {

        if (in_array($directory, array('select', 'update', 'insert', 'delete')))
        {
            if (isset(self::$stash_dir))
            {
                $file = self::$stash_dir.'/queries/'.$directory.'/'.$query_name.'.sql';

                if (file_exists($file))
                {
                    return trim(file_get_contents($file));
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
