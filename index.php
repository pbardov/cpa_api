<?php
/**
 * Этот файл только для тестирования возможностей API обмена
 */
require 'CrmXchg.php';
$auth = require 'auth.php';

function getParam($param, $default = NULL)
{
    if (isset($_REQUEST[$param]))
        return $_REQUEST[$param];
    else
        return $default;
}

function print_r_reverse($in) {
    $lines = explode("\n", trim($in));
    if (trim($lines[0]) != 'Array') {
        // bottomed out to something that isn't an array
        return trim($in);
    } else {
        // this is an array, lets parse it
        if (preg_match("/(\s{5,})\(/", $lines[1], $match)) {
            // this is a tested array/recursive call to this function
            // take a set of spaces off the beginning
            $spaces = $match[1];
            $spaces_length = strlen($spaces);
            $lines_total = count($lines);
            for ($i = 0; $i < $lines_total; $i++) {
                if (substr($lines[$i], 0, $spaces_length) == $spaces) {
                    $lines[$i] = substr($lines[$i], $spaces_length);
                }
            }
        }
        array_shift($lines); // Array
        array_shift($lines); // (
        array_pop($lines); // )
        $in = implode("\n", $lines);
        // make sure we only match stuff with 4 preceding spaces (stuff for this array and not a nested one)
        preg_match_all("/^\s{4}\[(.+?)\] \=\> /m", $in, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        $pos = array();
        $previous_key = '';
        $in_length = strlen($in);
        // store the following in $pos:
        // array with key = key of the parsed array's item
        // value = array(start position in $in, $end position in $in)
        foreach ($matches as $match) {
            $key = $match[1][0];
            $start = $match[0][1] + strlen($match[0][0]);
            $pos[$key] = array($start, $in_length);
            if ($previous_key != '') $pos[$previous_key][1] = $match[0][1] - 1;
            $previous_key = $key;
        }
        $ret = array();
        foreach ($pos as $key => $where) {
            // recursively see if the parsed out value is an array too
            $ret[$key] = print_r_reverse(substr($in, $where[0], $where[1] - $where[0]));
        }
        return $ret;
    }
}

$method = getParam('method');
$paramsStr = getParam('params');
if (!empty($paramsStr)) {
    $params = json_decode($paramsStr, true);
    if (!is_array($params)) {
        $params = print_r_reverse($paramsStr);
    }
} else {
    $params = array();
}
if (!is_array($params))
    $params = array();

$callStr = '';
$return  = NULL;

if (!empty($method)) {
    try {
        $api     = new CrmXchg($auth['client'], $auth['secret'], $auth['url']);
        $callStr = "call_user_func_array(array(\$api, $method), " . print_r($params, true) . ")";
        $return  = call_user_func_array(array($api, $method), $params);
    } catch (Exception $ex) {
        $error = array(
            'code'    => $ex->getCode(),
            'message' => $ex->getMessage(),
            'file'    => $ex->getFile(),
            'line'    => $ex->getLine(),
        );
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Пример и тестирование API CRM</title>
    </head>
    <body>
        <form action="index.php" method="POST">
            <div>
                <label for="method">Метод API</label><br />
                <select id="method" name="method" value="<?php echo $method; ?>" >
                    <option>addOrder</option>
                    <option>getStatus</option>
                    <option>getStatusR</option>
                </select>
            </div>
            <div>
                <label for="params">Параметры (json или print_r):</label><br />
                <textarea id="params" name="params"><?php echo $paramsStr; ?></textarea>
            </div>
            <input type="submit" value="Отправить" />
        </form>
        <div>
            <h3>Код вызова метода:</h3>
            <div><?php echo $callStr; ?></div>
            <h3>Результат:</h3>
            <pre><?php print_r($return); ?></pre>
            <?php if (isset($error)) : ?>
                <h3>Ошибка:</h3>>
                <pre><?php print_r($error); ?></pre>
            <?php endif; ?>
        </div>
    </body>
</html>
