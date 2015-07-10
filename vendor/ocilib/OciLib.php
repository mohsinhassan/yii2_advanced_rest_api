<?php
namespace OciLib;
class OciLib {
    public function ociConnect($dbUser,$dbPass,$dbName)
    {
        $c = oci_connect($dbUser,$dbPass,$dbName);
        return $c;
    }
}