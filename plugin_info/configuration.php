<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
  
    <div class="form-group">
      <label class="col-md-4 control-label">{{Email}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le mail}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="email"/>
      </div>
    </div>
          
    <div class="form-group">
      <label class="col-md-4 control-label">{{password}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le password}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="password"/>
      </div>
    </div>
          
    <div class="form-group">
      <label class="col-md-4 control-label">{{Global param 3}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Sélectionnez du paramètre 3 du plugin}}"></i></sup>
      </label>
      <div class="col-md-4">
        <select class="configKey form-control" data-l1key="param3">
          <option value=""></option>
          <option value="value1">value1</option>
          <option value="value2">value2</option>
        </select>
      </div>
    </div>
          
    <div class="form-group">
      <label class="col-md-4 control-label">{{Global param 3}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Synchro}}"></i></sup>
      </label>
      <div class="col-md-4">
                <a class="btn btn-info bt_login"><i id='synchydrolink' class="fa fa-refresh"></i>
                Synchroniser les modules avec le compte Heatzy<span id="synchydrolink"></span>
                </a>
      </div>
    </div>

          
  </fieldset>
</form>
          

<script>

$('.bt_login').on('click',function(){
    //$('#div_alert').showAlert({message: 'Synchronisation en cours...', level: 'info'});
    $.fn.showAlert({message: 'Synchronisation en cours...', level: 'info'});

    $('#synchydrolink').addClass('fa-spin');

    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // méthode de transmission des données au fichier php
        url: "plugins/hydrolinkhome/core/ajax/hydrolinkhome.ajax.php", // url du fichier php
        data: {
            action: "synchronise",
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $.fn.showAlert({message: data.result, level: 'danger'});
                return;
            }
          
          $.fn.showAlert({message: 'Synchronisation de ' + data.result + ' module(s)', level: 'info'});
          $('#synchydrolink').append(' : ' + data.result + ' module(s)');
        }
    });

    $('#synchydrolink').removeClass('fa-spin');
});

</script>