<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

defined('MOODLE_INTERNAL') || die();

// Spanish (es) translations

$string['pluginname'] = 'Regla de acceso al cuestionario del Respondus LockDown Browser';
$string['requirelockdownbrowser'] = 'La seguridad del explorador debe establecerse a través del panel del LockDown Browser';
$string['lockdownbrowsernotice'] = 'Este cuestionario se ha configurado para que los estudiantes solo puedan responderlo utilizando Respondus LockDown Browser.';
$string['noblockversion'] = 'La extensión de Respondus LockDown Browser para Moodle no está correctamente instalada. Falta el complemento de bloqueo o no se puede determinar su versión.';
$string['noruleversion'] = 'La extensión de Respondus LockDown Browser para Moodle no está correctamente instalada. Falta el complemento de la regla de acceso al cuestionario o no se puede determinar su versión.';
$string['invalidversion'] = 'La extensión de Respondus LockDown Browser para Moodle no está correctamente instalada. El complemento de bloqueo no coincide con la versión del complemento de la regla de acceso al cuestionario.';
$string['errnofunc'] = 'Faltan funciones {$a}';
$string['errdepend'] = 'Error de dependencia: {$a}';
$string['onesessionenabled'] = 'La regla de acceso al cuestionario para sesiones simultáneas del Bloqueo está habilitada para este cuestionario. Este regla no es compatible con la extensión del Respondus LockDown Browser para Moodle. Las conexiones simultáneas de bloqueo deben estar deseleccionadas en los ajustes del cuestionario.';
// Trac #4402
$string['privacy:metadata'] = 'La extensión de Respondus LockDown Browser para el complemento de la regla de acceso al cuestionario Moodle no almacena ningún dato personal.';

// don't translate anything below this line

// quiz browser security setting option key; must match same string in LDB block
$string['browsersecuritychoicekey'] = 'lockdownbrowser';
