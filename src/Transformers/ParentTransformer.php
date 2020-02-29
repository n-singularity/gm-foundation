<?php

namespace Nsingularity\GeneralModul\Foundation\Transformers;

use Nsingularity\GeneralModul\Foundation\Exceptions\CustomMessagesException;

trait ParentTransformer
{
    /**
     * @param string $arrayType
     * @param $include
     * @return array|mixed
     * @throws CustomMessagesException
     */
    public function generateTransformer($arrayType = "", $include = "")
    {
        $function = str_replace("_", " ", $arrayType);
        $function = ucwords($function);
        $function = str_replace(" ", "", $function);
        $function = "transformer" . $function;

        if (!method_exists($this, $function)) {
            $function = "transformerDefault";
        }

        if (!method_exists($this, $function)) {
            customException("default transformer not set");
        }

        $result = call_user_func_array(array($this, $function), array($this));
        $this->setInclude($result, $this, $this->parseInclude($include));
        $result = antiXss($result);
        $result = coreHtmlTagAllow($result);

        return $result;
    }

    private function parseInclude($include)
    {
        $arrayInclude = [];

        if (is_array($include)) {
            $arrayInclude = $include;
        } else {
            $includeExploded = explode(",", $include);
            foreach ($includeExploded as $value) {
                $subIncludeExplode = explode(".", $value);
                if (count($subIncludeExplode)) {
                    $arrayInclude[$subIncludeExplode[0]] = $this->parseSubInclude(1, $subIncludeExplode);
                }
            }
        }
        return $arrayInclude;
    }

    private function parseSubInclude($index, array $subInclude)
    {
        if (@$subInclude[$index]) {
            return [$subInclude[$index] => $this->parseSubInclude($index + 1, $subInclude)];
        }

        return [];
    }

    private function setInclude(&$result, $entity, array $allInclude)
    {
        foreach ($allInclude as $include => $subInclude) {
            $function = str_replace("_", " ", $include);
            $function = ucwords($function);
            $function = str_replace(" ", "", $function);
            $function = "include" . $function;

            if (method_exists($entity, $function)) {
                $result[$include] = call_user_func_array(array($entity, $function), array($entity, $subInclude));
            }
        }
    }
}
