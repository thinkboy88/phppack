<?php
/***********************QQ音乐类文件***********************/
/**
 * 示例:
 * $music = new QQMusic();
 * $music -> search('告白气球') -> getMusic(0);
 */
//$music = new QQMusic();
//echo $music -> getNew100() -> getMusic(0) . ' - ' . $music -> getSongName(0) . '<br />';
class QQMusic {
    private $url;
    private $callback;
    private $songs;
    private $vkey;
    private $songmid;
    private $filename;
    
    public function __construct() {
        $guid = '135505666';     //可以自定义，保证取vkey和播放时候相同，暂时写死
        $this -> url = [
            //搜索接口
            'search' => 'https://c.y.qq.com/soso/fcgi-bin/client_search_cp?aggr=1&cr=1&flag_qc=0&p=%s&n=%s&w=%s',
            //翻唱50 or 随机50首
            'cover' => 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_toplist_cp.fcg?g_tk=5381&uin=0&format=json&inCharset=utf-8&outCharset=utf-8¬ice=0&platform=h5&needNewCode=1&tpl=3&page=detail&type=top&topid=36&_=1520777874472',
            //新歌100首
            'newsong' => 'https://c.y.qq.com/v8/fcg-bin/fcg_v8_toplist_cp.fcg?g_tk=5381&uin=0&format=json&inCharset=utf-8&outCharset=utf-8&ice=0&platform=h5&needNewCode=1&tpl=3&page=detail&type=top&topid=27&_=1519963122923',
            //验证用的vkey
            'vkey' => 'https://c.y.qq.com/base/fcgi-bin/fcg_music_express_mobile3.fcg?format=json205361747&platform=yqq&cid=205361747&songmid=%s&filename=%s&guid=1008612580',
            //获得播放歌曲的url
            'song' => 'http://ws.stream.qqmusic.qq.com/%s?fromtag=0&guid=1008612580&vkey=%s',
            //第三方播放接口，封装了vkey，可以听vip的歌曲
            'autosong' => 'https://v1.itooi.cn/tencent/url?id=%s&quality=192',
        ];
    }
    
    /**
     * @parme n 显示的条数
     * @parme p 显示的页数
     * @parme keyword 搜索的关键词
     */
    public function search($keyword, $n = 2, $p = 1) {
        $this->songs = null;    //先初始化songs

        $curl = sprintf($this->url['search'], $p, $n, urlencode($keyword)); //callback()

        $this->callback = substr($this -> _request($curl, true, 'GET'), 9, -1); 

        $this->songs = json_decode($this->callback, true)['data']['song']['list'];
        return $this;
    }

    /**
     * @parme index 歌曲列表的第几个，必须小于search中设置的显示条数
     * @parme usekey 是否使用vkey
     */
    public function getMusic($index, $useKey = false) {
        //没有歌曲就提前退出
        if(empty($this->songs[$index])) return false;

        if(!$useKey) {
            return sprintf($this -> url['autosong'], $this->songs[$index]['songmid']);
        }

        else {
            $this -> _getKey($index);
            
            return sprintf($this->url['song'], $this->filename, $this->vkey);
        }
    }

    //获得票据vkey
    private function _getKey($index) {
        //准备请求需要的参数
        $this -> songmid = $this -> songs[$index]['songmid'];
        //$songmid = '003lghpv0jfFXG';
        $this -> filename = "C400" . $this -> songmid . ".m4a";
        $curl = sprintf($this->url['vkey'], $this -> songmid, $this -> filename);
        //请求到vkey
        $result = json_decode($this -> _request($curl), true);
       
        $this -> vkey = isset($result['data']['items'][0]['vkey']) ? $result['data']['items'][0]['vkey'] : ''; 
        return $this;
    }

    //获得歌曲名字
    public function getSongName($index) {
        if(empty($this->songs[$index])) return false;

        return $this -> songs[$index]['songname'];
    }

    //获得歌手名字
    public function getSingerName($index) {
        if(empty($this->songs[$index])) return false;

        return $this -> songs[$index]['singer'][0]['name'];
    }

    //获得50首翻唱歌曲
    public function getCover50() {
        $this -> songs = null;

        $this->callback = $this -> _request($this->url['cover'], true, 'GET');
        
        $songlist = json_decode($this->callback, true)['songlist'];

        foreach($songlist as $coverSong) {
            $this->songs[] = $coverSong['data'];
        }

        return $this;
    }

    //获得50首翻唱歌曲
    public function getNew100() {
        $this -> songs = null;

        $this->callback = $this -> _request($this->url['newsong'], true, 'GET');
        
        $songlist = json_decode($this->callback, true)['songlist'];

        foreach($songlist as $coverSong) {
            $this->songs[] = $coverSong['data'];
        }

        return $this;
    }


    //其他接口扩展中！！！！！！！！！！！！！！！！！



    //发送请求头
    private function _request($curl, $https=true, $method='GET', $data=null, $isfile=false) {
		$ch = curl_init();
		//解决上传不能获取数据的问题，7.0+已经废弃，低版本备用
		//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);

		curl_setopt($ch, CURLOPT_URL, $curl);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if($https) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			@curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
		}
		if($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		$content = curl_exec($ch);
		curl_close($ch);
		return $content;
    }
    
    //封装
    public function getAttrSongs() {
        return (isset($this -> songs) ? $this -> songs : null);
    }
    public function getAttrVkey() {
        return (isset($this -> vkey) ? $this -> vkey : null);
    }
}