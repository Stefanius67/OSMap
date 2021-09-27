<?php
declare(strict_types=1);

namespace SKien\ExtDOM;

/**
 * extends DOMDocumenta with few little helpers to make
 * XML access/creation a little easier
 *
 *  used by:
 *  lib\Helper\PHPInfoParser
 *  lib\StatesInfo
 *  lib\OSMap
 *
 * @package lib\Helper
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class ExtDOMDocument extends \DOMDocument
{
    /**
     * Create a ExtDOMDocument.
     * @param string $strVersion
     * @param string $strEncoding
     */
    public function __construct(string $strVersion = '1.0', string $strEncoding = 'UTF-8')
    {
        parent::__construct($strVersion, $strEncoding);
    }

    /**
     * Select nodes specified by XPath.
     * @param string $strPath
     * @param \DOMElement $oParent
     * @return \DOMNodeList
     */
    public function selectNodes(string $strPath, \DOMElement $oParent = null) : \DOMNodeList
    {
        $oXPath = new \DOMXPath($this);
        $oNodelist = $oXPath->query($strPath, $oParent);
        if ($oNodelist === false) {
            // PHP generates warning but with no further information about the query - so give little bit more info
            trigger_error('DOMXPath->query: malformed expression or the invalid contextnode (' . $strPath . ')', E_USER_NOTICE);
            $oNodelist = new \DOMNodeList();
        }
        return $oNodelist;
    }

    /**
     * Select first node specified by XPath.
     * @param string $strNode
     * @param \DOMElement $oParent
     * @return \DOMNode|null
     */
    public function selectSingleNode(string $strNode, \DOMElement $oParent = null) : ?\DOMNode
    {
        $oNode = null;
        $oNodelist = $this->selectNodes($strNode, $oParent);
        if ($oNodelist->length > 0) {
            $oNode = $oNodelist->item(0);
        }
        return $oNode;
    }

    /**
     * Get the value of the node specified by XPath.
     * @param string $strNode
     * @param \DOMElement $oParent
     * @return string|null
     */
    public function getNodeValue(string $strNode, \DOMElement $oParent = null) : ?string
    {
        $strValue = null;
        $oNode = $this->selectSingleNode($strNode, $oParent);
        if ($oNode != null) {
            $strValue = $oNode->nodeValue;
        }
        return $strValue;
    }

    /**
     * Get the name of the root element.
     * @return string|null
     */
    public function getRootName() {
        $strName = null;
        if ($this->documentElement) {
            $strName = $this->documentElement->nodeName;
        }
        return $strName;
    }

    /**
     * Create new DOMElement and append it to given parent.
     * @param string $strName
     * @param string $strValue
     * @param \DOMElement $oParent
     * @return \DOMElement|false
     */
    public function addChild(string $strName, string $strValue = '', \DOMElement $oParent = null)
    {
        $oChild = $this->createElement($strName, $strValue);
        if ($oChild !== false) {
            $oParent ? $oParent->appendChild($oChild) : $this->appendChild($oChild);
        }

        return $oChild;
    }
}
