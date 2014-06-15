<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication\Adapter;

use Zend\Authentication\Adapter\Http as HttpAuthBase;


/**
 * Http Auth Adapter
 */
class Http extends HttpAuthBase
{
	/**
	 * @var string
	 */
	protected $basicHeader = 'Basic';
	
	/**
	 * @var string
	 */
	protected $digestHeader = 'Digest';
	
	/**
	 * Set basic header
	 * 
	 * @param string $basicHeader
	 * @return \ZF\MvcAuth\Authentication\Adapter\Http
	 */
	public function setBasicHeader($basicHeader) {
        $this->basicHeader = $basicHeader;
        return $this;
	}

	/**
	 * Set digest header
	 *
	 * @param string $digestHeader
	 * @return \ZF\MvcAuth\Authentication\Adapter\Http
	 */
	public function setDigestHeader($digestHeader) {
        $this->digestHeader = $digestHeader;
        return $this;
	}
	
	/**
	 * return basic header
	 * 
	 * @return string
	 */
	public function getBasicHeader() {
        return $this->basicHeader;
	}
	
	/**
	 * return digest header
	 * 
	 * @return string
	 */
	public function getDigestHeader() {
        return $this->digestHeader;
	}
	
	/**
	 * Basic Header
	 *
	 * Generates a Proxy- or WWW-Authenticate header value in the Basic
	 * authentication scheme.
	 *
	 * @return string Authenticate header value
	 */
	protected function _basicHeader()
	{
		return $this->basicHeader.' realm="' . $this->realm . '"';
	}
	
	/**
	 * Digest Header
	 *
	 * Generates a Proxy- or WWW-Authenticate header value in the Digest
	 * authentication scheme.
	 *
	 * @return string Authenticate header value
	 */
	protected function _digestHeader()
	{
		$wwwauth = $this->digestHeader.' realm="' . $this->realm . '", '
				. 'domain="' . $this->domains . '", '
						. 'nonce="' . $this->_calcNonce() . '", '
								. ($this->useOpaque ? 'opaque="' . $this->_calcOpaque() . '", ' : '')
								. 'algorithm="' . $this->algo . '", '
										. 'qop="' . implode(',', $this->supportedQops) . '"';
	
		return $wwwauth;
	}
	
}
