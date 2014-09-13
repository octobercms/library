<?php namespace October\Rain\Routing;
use Illuminate\Routing\UrlGenerator as UrlGeneratorBase;
use File;

class UrlGenerator extends UrlGeneratorBase{
	/**
	 * Generate a URL to an application asset.
	 *
	 * @param  string  $path
	 * @param  bool    $secure
	 * @return string
	 */
	public function asset($path, $secure = null)
	{
		if ($this->isValidUrl($path)) return $path;
		if(trans('backend::lang.layout.direction') == 'rtl'){
			$pathParts = pathinfo($path);
			$rtlPath = $pathParts['dirname'] .'/' . $pathParts['filename'] . '-rtl.' . $pathParts['extension'];
			if(File::isFile(public_path() . '/' . $rtlPath)){
				$path = $rtlPath;
			}

		}
		return parent::asset($path,$secure);
	}	
}