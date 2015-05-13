<?php
require_once("../config.php");

header("Content-type: text/css");

//calaculate the button hover color
$colorVals;
preg_match_all("/\d{1,3}/", $local['dialog_color'], $colorVals);
$colorVals = array_pop($colorVals);

if(count($colorVals) == 3)
{
    list($r, $g, $b) = array_map('intval', $colorVals);

    $r = min(255, $r + 20);
    $g = min(255, $g + 20);
    $b = min(255, $b + 20);
    
    $highlight_color = "rgb(" . $r . "," . $g . "," . $b . ")";
}
else {
    $highlight_color = $local['dialog_color'];
}
?>

/*
Millenium Item Request Aggregation - Dialog Styling
*/
.ira-overlay {
    background: #000;
    opacity: .7;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 601;
}

.ira-dialog {
    position: fixed;
    top: 40%;
    left: 50%;
    width: 40em;
    margin-left: -20em;
    transform: translateY(-50%);
    background: #fff;
    border: 1px #aaa solid;
    z-index: 700;
    box-shadow: 2px 2px 2px rgba(0,0,0,.5);
    font-family: "Arial";
    color: #58595b;
}
.ira-dialog-header {
    color: #777;
    font-size: 1.3em;
    padding: .2em;
    padding-bottom: 4px;
    margin: .8rem .8rem 1px;
    text-align: left;
    border-bottom: 1px #ccc solid;
    font-weight: bold;
}
.ira-dialog-header i {
    color: #58595B;
    margin-left: 5px;
}
.ira-close-dialog {
    position: absolute;
    top: .3em;
    right: .2em;
    color: #585858;
    background: 0;
    border: 0;
    font-size: 1.8em;
}
.ira-close-dialog:hover {
    color: #f00;   
}
.ira-dialog-content {
    background: #fff;
    min-height: 10em;
    margin: .75em 1.5em 1.5em;
}
.ira-dialog img {
    padding: 2em;
}
.ira-dialog a, .ira-dialog a:visited  {
    color: <?=$local['dialog_color']?>;
}
.ira-dialog-content p {
    padding: .75em 0;
    margin: 0;
    font-size: 1em;
    text-align: left;
}
.ira-dialog .fulfillment-disclaimer {
    position: relative;
    display: inline-block;
}
.ira-dialog .fulfillment-disclaimer:after {
    content: "*";
    color: #f00;
}
.ira-dialog .fulfillment-disclaimer:hover:before {
    content: "This is only an estimate, and your material may arrive faster or more slowly.";
    display: block;
    position: absolute;
    z-index: 10;
    background: #ddd;
    color: #222;
    padding: .5em;
    border: 1px #aaa solid;
    left: 0;
    top: 1.5em;
    width: 20em;
    box-shadow: 2px 2px 2px rgba(0,0,0,.3);
}
.ira-dialog form, .ira-status.result {
    text-align: left;
    background: #eee;
    padding: 1em 0 0;
    border: 1px #ccc solid;
}
.ira-status.result {
    padding: 1em;
    font-weight: bold;
}
.ira-status.success {
    background: rgb(200,235,200);
}
.ira-status.error {
    color: #500;
    font-weight: bold;
}
.ira-status p {
    font-weight: normal;
}
.ira-dialog label {
    font-weight: bold;
    display: inline-block;
    width: 8.5em;
    font-size: 1.1em;
    padding: .2em 1em .2em 0;
    text-align: right;
}
.ira-dialog input {
    font-size: 1.3em;
    border: 1px #aaa solid;
    padding: .3em;
    width: 18em;
    margin: .2em;
    border-radius: 3px;
}
.ira-req-button {
    display: block;
    background: <?=$local['dialog_color']?>;
    color: #fff;
    padding: .4em 0;
    border: 0;
    border-top: 0px #000 solid;
    font-size: 1.5em;
    width: 100%;
    margin: 1em 0 0;
    text-shadow: 1px 1px 1px rgba(0,0,0,.5);
    cursor: pointer;
    transition: all .5s;
    font-weight: bold;
}
.ira-req-button:hover {
    background: <?=$highlight_color?>;
    transition: all .5s;
}
p.ira-server-error {
    font-size: .9em;
    color: #c00;
    background: #ccc;
    padding: .5em;
    margin: .5em -.5em;
}