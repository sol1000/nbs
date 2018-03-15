<?php
namespace Sung\Mvc;

class DB extends \PDO{
    public $engine;
    public $host; 
    public $port; 
    public $name; 
    public $user; 
    public $pass; 

    public function __construct($db_host, $db_port, $db_name, $db_user, $db_pass){
        $this->engine = 'mysql';
        $this->host = $db_host;
        $this->port = $db_port;
        $this->name = $db_name;
        $this->user = $db_user;
        $this->pass = $db_pass;
        $dns = $this->engine.':dbname='.$this->name.";host=".$this->host.";port=".$this->port.";charset=utf8";
        try {
            parent::__construct($dns, $this->user, $this->pass);
        }catch (PDOException $e) {
            return false;
        }
    }
    public function isMySQLFunc($function) {
        $arr_functions = array('abs','acos','adddate','addtime','aes_decrypt','aes_encrypt','any_value','area','asbinary','aswkb','ascii','asin','astext','aswkt','asymmetric_decrypt','asymmetric_derive','asymmetric_encrypt','asymmetric_sign','asymmetric_verify','atan','atan2','atan','avg','benchmark','bin','bit_and','bit_count','bit_length','bit_or','bit_xor','buffer','cast','ceil','ceiling','centroid','char_length','char','character_length','charset','coalesce','coercibility','collation','compress','concat_ws','concat','connection_id','contains','conv','convert_tz','convert','convexhull','cos','cot','count','crc32','create_asymmetric_priv_key','create_asymmetric_pub_key','create_dh_parameters','create_digest','crosses','curdate','current_date','current_time','current_timestamp','current_user','curtime','database','date_add','date_format','date_sub','date','datediff','day','dayname','dayofmonth','dayofweek','dayofyear','decode','default','degrees','des_decrypt','des_encrypt','dimension','disjoint','distance','elt','encode','encrypt','endpoint','envelope','equals','exp','export_set','exteriorring','extract','extractvalue','field','find_in_set','floor','format','found_rows','from_base64','from_days','from_unixtime','geomcollfromtext','geometrycollectionfromtext','geomcollfromwkb','geometrycollectionfromwkb','geometrycollection','geometryn','geometrytype','geomfromtext','geometryfromtext','geomfromwkb','geometryfromwkb','get_format','get_lock','glength','greatest','group_concat','gtid_subset','gtid_subtract','hex','hour','if','ifnull','in','inet_aton','inet_ntoa','inet6_aton','inet6_ntoa','insert','instr','interiorringn','intersects','interval','is_free_lock','is_ipv4_compat','is_ipv4_mapped','is_ipv4','is_ipv6','is_used_lock','isclosed','isempty','isnull','issimple','json_append','json_array_append','json_array_insert','json_array','json_contains_path','json_contains','json_depth','json_extract','json_insert','json_keys','json_length','json_merge','json_object','json_quote','json_remove','json_replace','json_search','json_set','json_type','json_unquote','json_valid','last_insert_id','lcase','least','left','length','linefromtext','linefromwkb','linestring','ln','load_file','localtime','locate','log','log10','log2','lower','lpad','ltrim','make_set','makedate','maketime','master_pos_wait','max','mbrcontains','mbrcoveredby','mbrcovers','mbrdisjoint','mbrequal','mbrequals','mbrintersects','mbroverlaps','mbrtouches','mbrwithin','md5','microsecond','mid','min','minute','mlinefromtext','multilinestringfromtext','mlinefromwkb','multilinestringfromwkb','mod','month','monthname','mpointfromtext','multipointfromtext','mpointfromwkb','multipointfromwkb','mpolyfromtext','multipolygonfromtext','mpolyfromwkb','multipolygonfromwkb','multilinestring','multipoint','multipolygon','name_const','now','nullif','numgeometries','numinteriorrings','numpoints','oct','octet_length','old_password','ord','overlaps','password','period_add','period_diff','pi','point','pointfromtext','pointfromwkb','pointn','polyfromtext','polygonfromtext','polyfromwkb','polygonfromwkb','polygon','position','pow','power','quarter','quote','radians','rand','random_bytes','release_all_locks','release_lock','repeat','replace','reverse','right','round','row_count','rpad','rtrim','schema','sec_to_time','second','session_user','sha1','sha','sha2','sign','sin','sleep','soundex','space','sqrt','srid','startpoint','std','stddev_pop','stddev_samp','stddev','str_to_date','strcmp','subdate','substr','substring_index','substring','subtime','sum','sysdate','system_user','tan','time_format','time_to_sec','time','timediff','timestamp','timestampadd','timestampdiff','to_base64','to_days','to_seconds','touches','trim','truncate','ucase','uncompress','uncompressed_length','unhex','unix_timestamp','updatexml','upper','user','utc_date','utc_time','utc_timestamp','uuid_short','uuid','validate_password_strength','values','var_pop','var_samp','variance','version','wait_for_executed_gtid_set','wait_until_sql_thread_after_gtids','week','weekday','weekofyear','weight_string','within','x','y','year','yearweek');
        if (in_array(strtolower($function), $arr_functions)) {
            return true;
        }else {
            return false;
        }
    }
}