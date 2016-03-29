<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class DocumentsField extends BaseField {

    protected $fillable = [
        'rid',
        'flid',
        'documents'
    ];

    public function keywordSearch(array &$args, $partial)
    {
        // TODO: Implement keyword_search() method.
    }

    public static function getMimeTypes(){
        $types=array();
        foreach(@explode("\n",@file_get_contents('http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types'))as $x)
            if(isset($x[0])&&$x[0]!=='#'&&preg_match_all('#([^\s]+)#',$x,$out)&&isset($out[1])&&($c=count($out[1]))>1)
                for($i=1;$i<$c;$i++)
                    $types[$out[1][$i]]=$out[1][0];
        return $types;
    }

}
