<?php
global $slash, $drive, $reqMethod;
$reqMethod = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : null;
if (in_array($reqMethod, array('OPTIONS', 'PROPFIND', 'LOCK', 'UNLOCK', 'COPY', 'MOVE', 'PUT', 'DELETE', 'MKCOL', 'PROPPATCH'))) {
	function removeTrailingSlash($path) {
		return (substr($path,-1) === '/') ? substr($path, 0, -1) : $path;
	}
	function listDriveCollection($files) {
		$o = '<D:multistatus xmlns:D="DAV:">';
		if (isset($files['list'])) {
		foreach ($files['list'] as $file) {
			if ($file['name'] === getConfig('passfile')) {
				continue;
			}
			$o .= '<D:response xmlns:lp1="DAV:" xmlns:g0="DAV:"><D:href>' . removeTrailingSlash($_SERVER['REQUEST_URI']) . '/' . rawurlencode($file['name']) . '</D:href><D:propstat><D:prop>';
			$o .= ($file['type'] === 'folder') ? '<lp1:resourcetype><D:collection/></lp1:resourcetype>' : '<lp1:resourcetype/>';
			if (!empty($file['size'])) {
				$o .= '<lp1:getcontentlength>' . $file['size'] . '</lp1:getcontentlength>';
			}
			if (!empty($file['time']) && ($file['time'] = strtotime($file['time'])) !== false) {
				$o .= '<lp1:getlastmodified>' . date('D, j M Y H:i:s \G\M\T', $file['time']) . '</lp1:getlastmodified><lp1:creationdate>' . date('Y-m-d\TH:i:s\Z', $file['time']) . '</lp1:creationdate>';
			}
			$o .= '</D:prop><D:status>HTTP/1.1 200 OK</D:status></D:propstat></D:response>';
		}
		}
		$o .= '</D:multistatus>';
		return $o;
	}
	function mainDAV($path) {
		global $slash, $drive, $reqMethod;
		if ($reqMethod === 'OPTIONS') {
			return output('', 200, array('DAV' => '1, 2', 'Accept-Charset' => 'utf-8', 'Allow' => 'OPTIONS, GET, HEAD, PROPFIND'));
		}
		$_SERVER['disktag'] = 'Kokomi233';
		if (strpos($path, "/{$_SERVER['disktag']}/") !== 0 && strpos($path, "{$_SERVER['disktag']}/") !== 0) {
	 		die("FAILED1!\n");
		}
		$path = str_replace("/{$_SERVER['disktag']}/", '', $path);
		$path = str_replace("{$_SERVER['disktag']}/", '', $path);
		$path = str_replace('..', '', $path);
		$slash = (strpos(__DIR__, ':')) ? '\\' : '/';
		$_SERVER['list_path'] = getListpath($_SERVER['HTTP_HOST']);
		if ($_SERVER['list_path'] === '') $_SERVER['list_path'] = '/';
		$drive = null;
		if (!driveisfine($_SERVER['disktag'], $drive)) {
			die("FAILED2!\n");
		}
	    if (getConfig('passfile') !== '') {
	    	$hiddenpass = gethiddenpass($path, getConfig('passfile'));
	    	if ($hiddenpass !== '' && ($_SERVER['PHP_AUTH_USER'] !== 'Lumine' || md5($_SERVER['PHP_AUTH_PW']) !== $hiddenpass)) {
		    	return output("FAILED3.\n", 401, array('WWW-Authenticate' => 'Basic realm="Secure Area"'));
		    }
	    }
		switch ($reqMethod) {
			case 'PROPFIND':
				$path1 = path_format($_SERVER['list_path'] . path_format($path));
				if ($path1 !== '/' && substr($path1,-1) === '/') $path1 = substr($path1, 0, -1);
				$files = $drive->list_files($path1);
				$c = listDriveCollection($files);
				return output($c, 207, array('Content-Type' => 'text/xml'));
				break;
			default:
				return output('', 403);
				break;
		}
	}
}
?>
