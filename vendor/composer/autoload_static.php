<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitea1a647372770fba8a117d50764a2564
{
    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'FPWD\\Bulk_Orders_Remover\\' => 25,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'FPWD\\Bulk_Orders_Remover\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitea1a647372770fba8a117d50764a2564::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitea1a647372770fba8a117d50764a2564::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
