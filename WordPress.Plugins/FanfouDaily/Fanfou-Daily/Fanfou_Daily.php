<?
class Fanfou_Daily
{
	var $iHtmlText;
	var $iHtmlTextIndex = 0;
	var $post_data = '';
	var $url = '';
	var $flag = 1;

	var $timeline = '';
	var $filter_on = 1;

	function Fanfou_Daily ($fanfouid, $timeline, $filteron) {
		$id = $fanfouid;
		$this->timeline = $timeline;
		$this->filter_on = $filteron;

		$this->url = 'http://fanfou.com/'.$id.'/p.';
	}

	function get_daily () {
		while ($this->flag) {
			$this->parse_post($this->url.$this->flag);
			if ( $this->flag ) {
				$this->flag += 1 ;
				$this->iHtmlTextIndex = 0 ;
			}
		}
		if ( $this->post_data != '' ) {
			return "<ol>\n".$this->post_data."</ol>\n";
		} else {
			return $this->post_data;
		}
	}

	function parse_post ($url) {
		$resp = $this->fetch_html($url);
		if ( !$this->is_success( $resp->status ) ) {
			$this->flag = 0;
			return false;
		}
		$this->iHtmlText = $resp->results;
		if ( $this->skipToStringInTag('<div id="stream" class="message">') == '' ) {
			$this->flag = 0;
			return false;
		}
		if ( $this->skipToStringInTag('</h3><ol><li>') == '' ) {
			$this->flag = 0;
			return false;
		}
		while ( $this->skipToStringInTag('class="content">') != '' ) {
			$item_content = $this->skipToStringInTag('</span>');
			if ( (!$this->filter_on ? false : $this->filtertrim($item_content)) ) continue;
			$item_info = $this->getinfo($this->skipToStringInTag('<span class="method">'));
			if ( is_array($item_info) ) {
				$item_time = strtotime($item_info[2]);
				if ( $item_time < $this->timeline ) {
					$this->flag = 0;
					break;
				} else if ( $item_time < ( $this->timeline + 86400 ) ) {
					$this->post_data .= '<li>'.$this->trimclass( $item_content );
					if ( $item_info[1] == '' ) {
						$this->post_data .= ' >> '.$item_info[2]."</li>\n";
					} else {
						$this->post_data .= ' >> <a href="http://fanfou.com'.$item_info[1].'">'.$item_info[2]."</a></li>\n";
					}
				}
			}
		}
		return true;
	}

	function fetch_html ($url, $headers = "" ) {
		if ( !isset($url) ) return false;
		require_once ABSPATH.WPINC.'/class-snoopy.php';
		$client = new Snoopy();
		if ( is_array($headers) ) {
			$client->rawheaders = $headers;
		}
		@$client->fetch($url);
		return $client;
	}

    function skipToStringInTag ($needle) {
        $pos = strpos ($this->iHtmlText, $needle, $this->iHtmlTextIndex);
        if ($pos === false) {
            return "";
        }
        $top = $pos + strlen($needle);
        $retvalue = substr ($this->iHtmlText, $this->iHtmlTextIndex, $top - $this->iHtmlTextIndex - strlen($needle));
        $this->iHtmlTextIndex = $top;
        return $retvalue;
    }

	function trimclass ($string) {
		$pattern = '/class="\w*"/i';
		$replacement = "";
		return preg_replace($pattern, $replacement, $string);
	}

	function getinfo ($string) {
		$pattern1 = '/href="(\S*)".*title="(.*)">/i';
		$pattern2 = '/title="(.*)">/i';
		$matches1 = array();
		$matches2 = array();
		if ( preg_match($pattern1, $string, $matches1) ) {
			return $matches1;
		} else if ( preg_match($pattern2, $string, $matches2) ){
			$matches1[0] = $matches1[1] = '';
			$matches1[2] = $matches2[1];
			return $matches1;
		} else {
			return '';
		}
	}

	function filtertrim ($string) {
		$filter = '@<a';
		if ( strpos($string, $filter) === 0 )
			return true;
		else
			return false;
	}

	function is_success ($sc) {
		return $sc >= 200 && $sc < 300;
	}
}
?>
