<?

class Helper_Search {

    /**
     * @param $solrDocuments
     * @return bool
     */
    static function sendDocuments($solrDocuments) {
   		if(count($solrDocuments)) {
   			$json = json_encode($solrDocuments);

   			if(self::addDocuments($json)) {
   				return true;
   			}
   		}
        return false;
   	}

    /**
     * SOLR Query
     *
     * @param $query
     * @return mixed
     */
    static function search($query) {
   		$url = Kohana::$config->load("solr.SOLR_SERVER"). '/select/?wt=json&' . http_build_query($query);
        $username = Kohana::$config->load("solr.SOLR_USER");
        $password = Kohana::$config->load("solr.SOLR_PW");

   		$ch = curl_init();
        if(!empty($username)) {
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        }

   		curl_setopt($ch, CURLOPT_ENCODING, "UTF-8" );
   		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
   		curl_setopt($ch, CURLOPT_URL, $url);
   		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
   		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
   		curl_setopt($ch, CURLOPT_VERBOSE, false);

   		$resulta = curl_exec($ch);
   		if (curl_errno($ch)) {
   			print curl_error($ch);
   		} else {
   			curl_close($ch);
   		}

   		return json_decode($resulta);
   	}

    /**
     * Add a SOLR Document to the SOLR index
     *
     * @param $json
     * @return bool
     */
    static function addDocuments($json) {
   		$url = Kohana::$config->load("solr.SOLR_SERVER"). '/update/json?wt=json&commit=true';
   		$username = Kohana::$config->load("solr.SOLR_USER");
   		$password = Kohana::$config->load("solr.SOLR_PW");

   		$ch = curl_init();
   		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
   		curl_setopt($ch, CURLOPT_HEADER, 0);
        if(!empty($username)) {
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        }
   		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
   		curl_setopt($ch, CURLOPT_URL, $url);

   		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
   		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
   		curl_setopt($ch, CURLOPT_VERBOSE, false);
   		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
   		curl_setopt($ch, CURLOPT_POST, 1);

   		$result = curl_exec($ch);

   		if (curl_errno($ch)) {
   			return false;
   		} else {
   			curl_close($ch);
   			return true;
   		}
   	}

    /**
     * Delete SOLR index
     *
     * @return bool
     */
    static function deleteIndex() {
   		$url = Kohana::$config->load("solr.SOLR_SERVER"). '/update?stream.body=<delete><query>*:*</query></delete>&commit=true';
   		$username = Kohana::$config->load("solr.SOLR_USER");
   		$password = Kohana::$config->load("solr.SOLR_PW");

   		$ch = curl_init();
   		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
   		curl_setopt($ch, CURLOPT_HEADER, 0);
        if(!empty($username)) {
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
        }
   		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
   		curl_setopt($ch, CURLOPT_URL, $url);

   		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
   		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
   		curl_setopt($ch, CURLOPT_VERBOSE, false);

   		$result = curl_exec($ch);
   		if (curl_errno($ch)) {
   			return false;
   		} else {
   			curl_close($ch);
   			return true;
   		}
   	}
}