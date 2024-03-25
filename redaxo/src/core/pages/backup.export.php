<?php

use Redaxo\Core\Core;
use Redaxo\Core\Database\Sql;
use Redaxo\Core\Filesystem\File;
use Redaxo\Core\Filesystem\Finder;
use Redaxo\Core\Filesystem\Path;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Translation\I18n;
use Redaxo\Core\Util\Str;

// Für größere Exports den Speicher für PHP erhöhen.
if (rex_ini_get('memory_limit') < 67_108_864) {
    @ini_set('memory_limit', '64M');
}

$success = '';
$error = '';

// ------------------------------ Requestvars
$exportfilename = rex_post('exportfilename', 'string');
$exporttype = rex_post('exporttype', 'string');
$exportdl = rex_post('exportdl', 'boolean');
$EXPTABLES = rex_post('EXPTABLES', 'array');
$EXPDIR = rex_post('EXPDIR', 'array');

if ('' == $exportfilename) {
    $exportfilename = Str::normalize(Core::getServerName()) . '_' . date('Ymd_Hi') . '_rex' . Core::getVersion();
}

if ($EXPTABLES) {
    $tables = Sql::factory()->getTables();

    foreach ($EXPTABLES as $k => $EXPTABLE) {
        if (!in_array($EXPTABLE, $tables)) {
            unset($EXPTABLES[$k]);
        }
    }
}

$csrfToken = rex_csrf_token::factory('backup');
$export = rex_post('export', 'bool');

if ($export && !$csrfToken->isValid()) {
    $error = I18n::msg('csrf_token_invalid');
} elseif ($export) {
    // ------------------------------ FUNC EXPORT

    $exportfilename = strtolower($exportfilename);
    /**
     * @psalm-taint-escape file
     * @psalm-taint-escape has_quotes
     * @psalm-taint-escape html
     * @psalm-taint-escape shell
     */
    $filename = preg_replace('@[^\.a-z0-9_\-]@', '', $exportfilename);

    if ($filename != $exportfilename) {
        $error = I18n::msg('backup_filename_updated');
        $exportfilename = $filename;
    } else {
        $hasContent = false;
        $header = '';
        $ext = 'sql' == $exporttype ? '.sql' : '.tar.gz';
        $exportPath = rex_backup::getDir() . '/';

        if (is_file($exportPath . $filename . $ext)) {
            $i = 1;
            while (is_file($exportPath . $filename . '_' . $i . $ext)) {
                ++$i;
            }
            $filename = $filename . '_' . $i;
        }

        if ('sql' == $exporttype) {
            // ------------------------------ FUNC EXPORT SQL
            $header = 'plain/text';

            $hasContent = rex_backup::exportDb($exportPath . $filename . $ext, $EXPTABLES);
        } elseif ('files' == $exporttype) {
            // ------------------------------ FUNC EXPORT FILES
            $header = 'tar/gzip';

            if (empty($EXPDIR)) {
                $error = I18n::msg('backup_please_choose_folder');
            } else {
                rex_backup::exportFiles($EXPDIR, $exportPath . $filename . $ext);
                $hasContent = true;
            }
        }

        if ($hasContent) {
            if ($exportdl) {
                $filename .= $ext;
                rex_response::sendFile($exportPath . $filename, $header, 'attachment');
                File::delete($exportPath . $filename);
                exit;
            }
            $success = I18n::msg('backup_file_generated_in') . ' ' . strtr($filename . $ext, '\\', '/');
        } elseif (empty($error)) { // if the user selected no files to export $error is already filled
            $error = I18n::msg('backup_file_could_not_be_generated') . ' ' . I18n::msg('backup_check_rights_in_directory') . ' ' . $exportPath;
        }
    }
}

if ('' != $success) {
    echo rex_view::success($success);
}
if ('' != $error) {
    echo rex_view::error($error);
}

$content = '';

$fragment = new rex_fragment();
$fragment->setVar('class', 'info', false);
$fragment->setVar('title', I18n::msg('backup_information'), false);
$fragment->setVar('body', '<p>' . I18n::msg('backup_intro_export') . '</p>', false);
echo $fragment->parse('core/page/section.php');

$content .= '<fieldset>';

$checkedsql = '';
$checkedfiles = '';

if ('files' == $exporttype) {
    $checkedfiles = ' checked="checked"';
} else {
    $checkedsql = ' checked="checked"';
}

$formElements = [];
$n = [];
$n['label'] = '<label for="rex-js-exporttype-sql">' . I18n::msg('backup_database_export') . '</label>';
$n['field'] = '<input type="radio" id="rex-js-exporttype-sql" name="exporttype" value="sql"' . $checkedsql . ' />';
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="rex-js-exporttype-files">' . I18n::msg('backup_file_export') . '</label>';
$n['field'] = '<input type="radio" id="rex-js-exporttype-files" name="exporttype" value="files"' . $checkedfiles . ' />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$radios = $fragment->parse('core/form/radio.php');

$formElements = [];
$n = [];
$n['label'] = I18n::msg('backup_export_select');
$n['field'] = $radios;
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/form.php');

$tableSelect = new rex_select();
$tableSelect->setMultiple();
$tableSelect->setId('rex-form-exporttables');
$tableSelect->setSize(20);
$tableSelect->setName('EXPTABLES[]');
$tableSelect->setAttribute('class', 'form-control');
$tables = Sql::factory()->getTables();
foreach ($tables as $table) {
    $tableSelect->addOption($table, $table);
    if (in_array($table, [Core::getTable('user'), Core::getTable('user_passkey'), Core::getTable('user_session')], true)) {
        continue;
    }
    // skip non rex_ tables
    if (!str_starts_with($table, Core::getTablePrefix())) {
        continue;
    }
    // skip rex_tmp_ tables
    if (str_starts_with($table, Core::getTablePrefix() . Core::getTempPrefix())) {
        continue;
    }

    $tableSelect->setSelected($table);
}

$formElements = [];
$n = [];
$n['header'] = '<div id="rex-js-exporttype-sql-div"' . ($checkedsql ? '' : ' style="display: none;"') . '>';
$n['label'] = '<label for="rex-form-exporttables">' . I18n::msg('backup_export_select_tables') . '</label>';
$n['field'] = $tableSelect->get();
$n['footer'] = '</div>';
$formElements[] = $n;

// Vorhandene Exporte auslesen
$selDirs = new rex_select();
$selDirs->setId('rex-form-exportdir');
$selDirs->setName('EXPDIR[]');
$selDirs->setMultiple();
$selDirs->setSelected($EXPDIR);
$selDirs->setStyle('class="form-control"');

$dir = Path::frontend();
$folders = Finder::factory($dir)
    ->dirsOnly()
    ->ignoreDirs('.*')
    ->ignoreDirs('redaxo')
;
$folders = iterator_to_array($folders);
$countFolders = count($folders);
if ($countFolders > 4) {
    $selDirs->setSize($countFolders);
}
foreach ($folders as $path => $_) {
    $file = Path::basename($path);
    $selDirs->addOption($file, $file);
}

$n = [];
$n['header'] = '<div id="rex-js-exporttype-files-div"' . ($checkedfiles ? '' : ' style="display: none;"') . '>';
$n['label'] = '<label for="rex-form-exportdir">' . I18n::msg('backup_export_select_dir') . '</label>';
$n['field'] = $selDirs->get();
$n['footer'] = '</div>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/form.php');

$checked0 = '';
$checked1 = '';

if ($exportdl) {
    $checked1 = ' checked="checked"';
} else {
    $checked0 = ' checked="checked"';
}

$formElements = [];

$n = [];
$n['label'] = '<label for="rex-form-exportdl-server">' . I18n::msg('backup_save_on_server') . '</label>';
$n['field'] = '<input type="radio" id="rex-form-exportdl-server" name="exportdl" value="0"' . $checked0 . ' />';
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="rex-form-exportdl-download">' . I18n::msg('backup_download_as_file') . '</label>';
$n['field'] = '<input type="radio" id="rex-form-exportdl-download" name="exportdl" value="1"' . $checked1 . ' />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$radios = $fragment->parse('core/form/radio.php');

$formElements = [];
$n = [];
$n['label'] = I18n::msg('backup_export_select_location');
$n['field'] = $radios;
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/form.php');

$formElements = [];

$n = [];
$n['label'] = '<label for="rex-form-exportfilename">' . I18n::msg('backup_filename') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="rex-form-exportfilename" name="exportfilename" value="' . rex_escape($exportfilename) . '" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/form.php');

$content .= '</fieldset>';

$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="export" value="' . I18n::msg('backup_db_export') . '">' . I18n::msg('backup_to_export') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', I18n::msg('backup_export'), false);
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/page/section.php');

$content = '
<form action="' . Url::currentBackendPage() . '" data-pjax="false" method="post">
    ' . $csrfToken->getHiddenField() . '
    ' . $content . '
</form>

<script type="text/javascript" nonce="' . rex_response::getNonce() . '">
    <!--

    (function($) {
        var currentShown = null;
        $("#rex-js-exporttype-sql, #rex-js-exporttype-files").click(function(){
            if(currentShown) currentShown.hide();

            var effectParamsId = "#" + $(this).attr("id") + "-div";
            currentShown = $(effectParamsId);
            currentShown.fadeIn();
        }).filter(":checked").click();
    })(jQuery);

    //-->
</script>';

echo $content;