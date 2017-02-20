<?php
ini_set('memory_limit', '256M');

$sc = new SpellCorrect();

echo "This is a tool help you to correct the words. please input q if you want to quit." . PHP_EOL;
while(1) {
    // ask for input  
    fwrite(STDOUT, "Enter the word: ");  
    // get input  
    $word = trim(fgets(STDIN)); 
    if ($word == "q") {
        break;
    }
    $correction = $sc->detect($word);
    var_dump("the most probability word is: \033[41;30;5m" . $correction . "\033[0m");
}

class SpellCorrect {

    private $WORDS;

    public function __construct() {
        $this->init();
    }

    public function init() {
        $file = fopen("big.txt", "r");
        while(!feof($file)) {
            Counter::add($this->words(fgets($file)));
        }
        $this->WORDS = Counter::counters();
    }

    public function probability($word) {
        return $this->WORDS[$word] / array_sum($this->WORDS);
    }

    public function detect($word) {
        $max = 0;
        $correction = $this->candidates($word);
        if (count($correction) == 1) return $correction[0];
        foreach ($correction as $word) {
            $p = $this->probability($word);
            if ($p > $max) {
                $max = $p;
                $result = $word;
            }
        }
        return $result;
    }

    public function words($text) {
        preg_match_all("/\w+/", strtolower($text), $words);
        return $words[0];
    }

    public function candidates($word) {
        $initWord = $this->known(array($word));
        $sourceCorrectWords = $this->correction($word);
        $correctWords = $this->known($sourceCorrectWords);
        if (! empty($initWord)) {
            return $initWord;
        } elseif (! empty($correctWords)) {
            return $correctWords;
        } else {
            return array($word);
        }
    }

    /**
     * 返回已知词语列表
     */
    public function known($list) {
        $knowns = array();
        foreach ($list as $word) {
            array_key_exists($word, $this->WORDS) && $knowns[] = $word;
        }
        return $knowns;
    }

    public function correction($word) {
        $deletion = $this->deletion($word);
        $transposition = $this->transposition($word);
        $replace = $this->replace($word);
        $insertion = $this->insertion($word);
        return array_merge($deletion, $transposition, $replace, $insertion);
    }

    public function doubleCorrection($correction) {
        $words = array();
        foreach ($correction as $word) {
            $words = array_merge($words, $this->correction($word));
        }
        return $words;
    }

    /**
     *  去除一个字符
     */
    public function deletion($word) {
        $list = array();
        $len = strlen($word);
        for ($index = 0; $index < $len; $index++) {
            $list[] = substr($word, 0, $index) . substr($word, $index+1);
        }
        return $list;
    }

    /**
     *  相邻位置交换
     */
    public function transposition($word) {
        $list = array();
        $len = strlen($word);
        for ($index = 0; $index < $len - 1; $index++) {
            $tmpWord = $word;
            $tmp = $tmpWord[$index];
            $tmpWord[$index] = $tmpWord[$index + 1];
            $tmpWord[$index + 1] = $tmp;
            $list[] = $tmpWord;
        }
        return $list;
    }

    /**
     *  某位置被替换
     */
    public function replace($word) {
        $list = array();
        $letters = 'abcdefghijklmnopqrstuvwxyz';
        $len = strlen($word);
        for ($index = 0; $index < $len; $index++) {
            $j = 0;
            while ($j < 26) {
                //$list[] = str_replace($word[$index], $letters[$j++], $word);
                $list[] = substr_replace($word, $letters[$j++], $index, 1);
            }
        }
        return $list;
    }

    /**
     * 某位置添加字符
     */
    public function insertion($word) {
        $list = array();
        $letters = 'abcdefghijklmnopqrstuvwxyz';
        $len = strlen($word);
        for ($index = 0; $index <= $len; $index++) {
            $j = 0;
            while ($j < 26) {
                $list[] = substr($word, 0, $index) . $letters[$j++] . substr($word, $index);
            }
        }
        return $list;
    }
}


/**
 *  目前这种方式太吃内存，可以再考虑考虑
 */
class Counter {

    private static $container = array();

    public static function add($words) {
        foreach ($words as $word) {
            self::$container[] = $word;
        }
    }

    public static function counters() {
        return array_count_values(self::$container);
    }
}
