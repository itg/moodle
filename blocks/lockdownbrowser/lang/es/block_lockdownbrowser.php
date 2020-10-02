<?php
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.

// Spanish (es) translations

$string['pluginname']      = 'Respondus LockDown Browser';
$string['lockdownbrowser'] = 'Respondus LockDown Browser';
$string['lockdownbrowser:addinstance'] = 'Agregar un nuevo bloqueo de lockdownbrowser';

// admin settings page
$string["blockdescheader"]     = "Descripción";
$string["blockdescription"]    = "Extensión de Respondus LockDown Browser para Moodle";
$string["blockversionheader"]  = "Versión actual";
$string["adminsettingsheader"] = "Ajustes de administración";

$string['adminsettingsheaderinfo'] =
    'Los valores de estos ajustes son proporcionados por Respondus. Si descarga el bloqueo
    del Respondus Campus Portal, los ajustes ya están incluidos en el bloqueo. Revise la
    Guía del administrador para obtener detalles adicionales.';

$string["adminstatus"] = "Estado del bloqueo";

$string["authenticationsettingsheader"]     = "Ajustes de autenticación";

$string["authenticationsettingsheaderinfo"] =
    "Estas son las credenciales para el usuario con las cuales se ejecutan los servicios web
    de Respondus LockDown Browser y Respondus Monitor. El usuario que ingresa debe ser uno de
    los administrador del sitio Moodle que aparece en Administración del
    sitio->Usuarios->Permisos->Administradores del sitio. Esta información nunca se transmite
    fuera de este servidor Moodle y todas las solicitudes de servicio web de Respondus Monitor
    se autentican mediante códigos de autenticación de mensajes basados en Hash. En caso de
    seleccionarse la opción \"Usar HTTPS para iniciar sesión\" en los ajustes
    Seguridad->Seguridad de HTTP, todas las solicitudes de servicios web de Respondus Monitor
    refuerzan el uso de HTTPS.";

$string["password"]                         = "Contraseña";
$string["passwordinfo"]                     = "Contraseña para el usuario de Respondus Monitor.";
$string["username"]                         = "Nombre de usuario";
$string["usernameinfo"]                     = "Nombre de usuario para Respondus Monitor.";

$string['servername'] = 'Nombre de servidor';

$string['servernameinfo'] =
    'Este ajuste debe coincidir con el nombre ingresado en el perfil del Respondus Campus Portal para este servidor de Moodle.';

$string['serverid']     = 'Identificación del servidor';
$string['serveridinfo'] = 'Identificación de la institución para este servidor Moodle. Asignado por Respondus.';
$string['serversecret'] = 'Secreto compartido';

$string['serversecretinfo'] =
    'Este ajuste debe coincidir con el secreto ingresado en el perfil del Respondus Campus Portal para este servidor de Moodle.';

$string['servertype']     = 'Tipo de licencia';
$string['servertypeinfo'] = 'De todo el Campus = 0, Para laboratorio = 1.';
$string['downloadurl']    = 'URL de descarga';

$string['downloadinfo'] =
    'Enlace para que los estudiantes descarguen el cliente del explorador. Deje en blanco si no desea mostrar un enlace en la página de intentos.';

$string['dashboard']         = 'Panel';
$string['quizzes']           = 'Cuestionarios';
$string['lockdown_settings'] = 'Ajustes del LockDown Browser';
$string['quiz']              = 'Cuestionario';
$string['disable']           = 'Deshabilitar';
$string['enable']            = 'Habilitar';
$string['ldb_required']      = 'Este examen requiere Respondus LockDown Browser.';
$string['monitor_required']  = 'Este examen requiere Respondus LockDown Browser con Respondus Monitor (cámara web).';

$string['block_status_ok'] = 'El estado del bloqueo es correcto.';

$string['module_installed_error']  =
    "Error: no se ha desinstalado el módulo /mod/lockdown. Consulte la Guía del administrador de LockDown Browser - Moodle.";

$string['session_cookie_not_set'] = 'Advertencia: error en la verificación de cookies de sesión Moodle.';

$string['ldb_quiz_count'] =
    'Advertencia: {$a} filas en la tabla de ajustes del cuestionario. Esto puede disminuir el rendimiento del panel LDB.';

$string['ldb_download_disabled'] = 'No se permite la descarga de LockDown Browser en este sitio.';
$string['iframe_error']          = 'Esta página requiere compatibilidad con iframes';

$string['errcmid'] = 'No hay ningún módulo de curso con el id. {$a}';
$string['errcourse'] = 'El curso está mal configurado';
$string['errquizid'] = 'No hay ningún cuestionario con el id. {$a}';
$string['errnocourse'] = 'Falta el curso con el id. {$a->course} al que pertenece el cuestionario con el id. {$a->id}';
$string['errnocm'] = 'Falta el módulo de curso para el cuestionario con el id. {$a}';
$string['errattempt'] = 'No existe tal ID de intento';
$string['errnoquiz1'] = 'Falta el cuestionario con el id. {$a->instance} que corresponde a este módulo de curso {$a->id}';
$string['errnoquiz2'] = 'Falta el cuestionario con el id. {$a->quiz} que pertenece al intento {$a->id}';

$string['noblockversion'] = 'La extensión de Respondus LockDown Browser para Moodle no está correctamente instalada. Falta el complemento de bloqueo o no se puede determinar su versión.';
$string['noruleversion'] = 'La extensión de Respondus LockDown Browser para Moodle no está correctamente instalada. Falta el complemento de regla de acceso al cuestionario o no se puede determinar su versión.';
$string['invalidversion'] = 'La extensión de Respondus LockDown Browser para Moodle no está correctamente instalada. El complemento de bloqueo no coincide con la versión del complemento de la regla de acceso al cuestionario.';

$string['nosessionparm'] = 'No se especificó ningún parámetro para la sesión.';
$string['noexamidparm'] = 'No se especificó ningún parámetro para el id. del examen.';
$string['nochallengeparm'] = 'No se especificó ningún parámetro para el desafío.';
$string['nolmsrooturl'] = 'No se encontró ningún URL raíz de LMS en la configuración de Moodle.';
$string['errsetsession'] = 'Error al establecer la cookie de la sesión.';
$string['prestartexambutton'] = 'Comenzar con el cuestionario';
$string['exitbrowserbutton'] = 'Salir del explorador';
$string['autolaunchpagetitle'] = 'Respondus LockDown Browser';
$string['errsessionmatch'] = 'La sesión no coincide con el inicio del explorador.';
$string['errsessionkey'] = 'Error al recuperar la clave de la sesión.';
$string['prestartpagetitle'] = 'Respondus LockDown Browser';
$string['prestartpagetext'] = 'Cargando...';

$string['nomanuallaunch'] = "LockDown Browser no debería iniciarse en forma manual. Use un explorador estándar (p.ej., Chrome, IE, Firefox, etc.) para desplazarse hasta el examen y LockDown Browser iniciará en forma automática cuando se requiera.";

$string['autolaunchbutton'] = 'Iniciar LockDown Browser';
$string['sessioninprogress'] = 'Sesión de Respondus LockDown Browser en curso';
$string['ldbdownlink'] = 'Descargar LockDown Browser';
$string['ldbchecklink'] = 'Verifique su configuración de LockDown Browser';
$string['errinvalidldbquiztype'] = 'Tipo de cuestionario LDB especificado no válido.';
$string['errinvalidsession'] = 'Error de sesión no válido';

$string['errchallengeresponse'] = "Está usando una versión de LockDown Browser que no es compatible. Actualice a la versión más reciente y vuelva a intentarlo.";

$string['errunknown'] = 'Error desconocido';
$string['errsessioncookiename'] = 'Nombre de cookie de sesión no configurado';
$string['errchallengecookiename'] = 'Nombre de cookie de desafío no configurado';
$string['errresponsecookiename'] = 'Nombre de cookie de respuesta no configurado';
$string['errprofilesecret'] = 'Secreto de perfil del servidor no configurado';
$string['errinstitutionid'] = 'La ID de la Institución no ha sido configurada';
$string['errsdk2015secret1'] = 'Secreto de inicio automático 1 no configurado';
$string['errsdk2015index'] = 'Índice de inicio automático no configurado';
$string['errsdk2015secret2'] = 'Secreto de inicio automático 2 no configurado';
$string['errsdk2015monitorcheck'] = 'URL de verificación de Respondus Monitor no configurado';
$string['errsdk2015ldbonlycheck'] = 'URL de verificación de Lockdown Browser no configurado';
$string['errsdk2015launchscheme'] = 'Esquema de inicio automático no configurado';
$string['errstandardclientidname'] = 'Nombre de la cookie de id. de cliente estándar no configurado';
$string['errsdk2015clientidname'] = 'Nombre de cookie de id. de cliente SDK no configurado';
$string['errsdk2015serveridname'] = 'Nombre de cookie de id. de servidor SDK no configurado';
$string['errsdk2015commandscheme'] = 'Esquema de comando SDK no configurado';
$string['errsdk2015commandexitb'] = 'Comando del explorador de salida SDK no configurado';
$string['errsdk2015securityvhigh'] = 'Comando de seguridad SDK no configurado';
$string['errsdk2015sessionparm'] = 'Parámetro de URL de sesión no configurado';
$string['errsdk2015examidparm'] = 'Parámetro de URL de id. de examen no configurado';
$string['errsdk2015ldbcheckparm'] = 'Parámetro de URL para verficiación LDB no configurado';
$string['errinvalidldbcheckparm'] = 'Valor del parámetro de URL para verificiación LDB no reconocido';
$string['errldbexamtypeldbonly'] = 'Examen tipo LDB-ONLY no configurado';
$string['errldbexamtypemonitor'] = 'Examen tipo MONITOR no configurado';
$string['errldbsessionnotactive'] = 'Sesión de LockDown Browser no activo';
$string['errsdk2015prestartfn'] = 'Nombre de función de preinicio SDK no configurado';
$string['errldbmonitorexitreopen'] = 'URL de salida y reapertura de LDB Monitor no configurado';

// Trac #4402
$string['privacy:metadata'] = 'La extensión de Respondus LockDown Browser para el complemento de bloqueo de Moodle no almacena ningún dato personal.';

// don't translate anything below this line

// quiz title decorations; must match what clients expect
$string['requires_ldb']      = '- Requires Respondus LockDown Browser';
$string['requires_webcam']   = '- Requires Respondus LockDown Browser + Webcam';

// quiz browser security setting option key; must match same string in LDB quiz access rule
$string['browsersecuritychoicekey'] = 'lockdownbrowser';
