<?php

class View_Helper_Avatar extends Zend_View_Helper_HtmlElement
{
    /**
     * @param int|string       $id
     * @param float|int|string $size
     * @param null|mixed       $attribs
     */
    public function avatar($id, $size = 'small', $attribs = null): void
    {
        // Attributs
        $attribs = $attribs ? $this->_htmlAttribs($attribs) : '';

        $src = DATA_PATH.sprintf('/uploads/avatars/%s/', $size);
        $file_path = REAL_DATA_PATH.DS.'uploads'.DS.'avatars'.DS.$size.DS.$id.'.jpg';
        echo sprintf("<img %s src='", $attribs).$src.(file_exists($file_path) ? $id : 'default').".jpg' alt='Avatar' />";
    }
}
