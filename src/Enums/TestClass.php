<?php declare(strict_types=1);


namespace App\Enums;


class TestClass
{

    public function testOne() {
        $a = 1;
        $b = "1";

        if ($b == $a) {
            return;
        }
    }

    public function testTwo() {
        $a = 1;
        $b = "1";

        if ($b == $a) {
            return;
        }
    }

    public function testThree() {
        $a = 1;
        $b = 1;

        if ($b == $a) {
            return;
        }
    }
}
