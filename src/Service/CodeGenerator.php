<?php

namespace App\Service;

class CodeGenerator
{

    private function generateLettersForCode(array $choices, ?string $exclude): array
    {
        $letters = $this->parseChoicesArray($choices);
        return $this->excludeChars($letters,$exclude);
    }

    public function generateCode(string $prefix, array $choices, ?string $exclude, int $length=10): string
    {
        $letters = $this->generateLettersForCode($choices,$exclude);
        $letters = implode('',$letters);

        return $prefix . substr(str_shuffle(str_repeat($letters, ceil($length / strlen($letters)))), 1, $length);
    }

    public function generateManyCodes(int $count, string $prefix, array $choices, ?string $exclude, int $length=10): array
    {
        $codes = [];
        $letters = $this->generateLettersForCode($choices,$exclude);
        $letters = implode('',$letters);

        while (count($codes) < $count) {
            $code = $prefix . substr(str_shuffle(str_repeat($letters, ceil($length / strlen($letters)))), 1, $length);
            if (!in_array($code, $codes)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    private function parseChoicesArray(array $choices): array
    {
        $letters = [];

        foreach ($choices as $choice)
        {
            switch ($choice){
                case '1':
                    $tmp = range(0,9);
                    break;
                case '2':
                    $tmp = range('a','z');
                    break;
                case '3':
                    $tmp = range('A', 'Z');
                    break;
                default:
                    $tmp =[];
                    break;
            }

            $letters = array_merge($letters,$tmp);
        }

        return $letters;
    }

    private function excludeChars(array $letters, ?string $exclude): array
    {
        $toExlude = explode(",",$exclude);

        foreach ($toExlude as $item)
        {
            $key = array_search(trim($item),$letters);
            if($key!==false)
            {
                unset($letters[$key]);
            }
        }

        return $letters;
    }
}