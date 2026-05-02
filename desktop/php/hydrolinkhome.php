<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
$plugin = plugin::byId('hydrolinkhome');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<!-- Boutons de gestion du plugin -->
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" onclick="window.open('https://github.com/bodbod44/jeedom-hydrolinkhome/blob/beta/docs/fr_FR/index.md', '_blank');">
				<i class="fas fa-book-open"></i>
				<br>
				<span>{{Documentation}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" onclick="window.open('https://community.jeedom.com/tag/plugin-hydrolinkhome', '_blank');">
				<i class="fas fa-ambulance"></i>
				<br>
				<span>{{Assistance}}</span>
			</div>
		</div>
		<legend><i class="fas fa-table"></i> {{Mes hydrolinkhomes}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Template trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				//$image_url = $eqLogic->getConfiguration('image_url', null) ;              
              	echo '<img src="'.$eqLogic->getConfiguration('image_url', "xx").'" onerror="this.src='."'".$eqLogic->getImage()."';".'"/>';      
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div> <!-- /.eqLogicThumbnailDisplay -->

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- barre de gestion de l'équipement -->
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet de configuration de l'équipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- Partie gauche de l'onglet "Equipements" -->
				<!-- Paramètres généraux et spécifiques de l'équipement -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>

							<legend><i class="fas fa-cogs"></i> {{Paramètres spécifiques}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Identifiant de l'appareil}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Identifiant de l'appareil}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" placeholder="{{Identifiant de l'appareil}}" readonly>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Description}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Description}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<textarea class="eqLogicAttr form-control" data-l1key="comment"></textarea>
								</div>
							</div>
						</div>

						<!-- Partie droite de l'onglet "Équipement" -->
						<!-- Affiche un champ de commentaire par défaut mais vous pouvez y mettre ce que vous voulez -->
						<div class="col-lg-6">
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>

							<div class="form-group">
								<label class="col-sm-4 control-label">{{Date création}}</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="createtime" title="{{Date de dernière communication}}" style="font-size : 1em;cursor : default;"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Date mise à jour}}</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="updatetime" title="{{Date de dernière mise à jour}}" style="font-size : 1em;cursor : default;"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom}}</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="user" title="{{Nom}}" style="font-size : 1em;cursor : default;"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Localisation}}</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="location" title="{{Localisation}}" style="font-size : 1em;cursor : default;"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{SSID Wifi}}</label>
								<div class="col-sm-6">
									<span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="wifi_ssid" title="{{SSID Wifi}}" style="font-size : 1em;cursor : default;"></span>
								</div>
							</div>
                                      
                            <!-- Champ de saisie de l'URL -->
                            <div class="form-group" style="display:none">
                                <label class="col-sm-4 control-label">{{URL de l'image}}</label>
                                <div class="col-sm-6">
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="image_url" placeholder="{{URL de l'image}}"/>
                                </div>
                            </div>

                            <!-- Zone d'affichage de l'image -->
                            <div class="form-group">
                                <label class="col-sm-4 control-label">{{Aperçu}}</label>
                                <div class="col-sm-6">
                                    <img src="" id="img_device_preview" class="img-responsive" style="max-height : 250px; border: 0px solid #ddd; padding: 5px; border-radius: 5px;" onerror="this.src='plugins/hydrolinkhome/core/template/images/error.jpg';"/>
                                </div>
                            </div>
                            
						</div>
					</fieldset>
				</form>
			</div><!-- /.tabpanel #eqlogictab-->

			<!-- Onglet des commandes de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
				<br><br>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
								<th style="min-width:200px;width:350px;">{{Nom}}</th>
								<th>{{Type}}</th>
								<th style="min-width:260px;">{{Options}}</th>
								<th>{{Etat}}</th>
								<th style="min-width:80px;width:200px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<script>
function printEqLogic(_eqLogic) {
    // ... votre code existant ...
    
    // On récupère l'url depuis la config et on l'injecte dans le src de l'image
    if (typeof _eqLogic.configuration.image_url !== 'undefined' && _eqLogic.configuration.image_url !== '') {
        $('#img_device_preview').attr('src', _eqLogic.configuration.image_url);
    } else {
        $('#img_device_preview').attr('src', 'plugins/hydrolinkhome/core/template/images/adoucisseur.jpg');
    }
}

$('body').off('change', '.eqLogicAttr[data-l1key=configuration][data-l2key=image_url]').on('change', '.eqLogicAttr[data-l1key=configuration][data-l2key=image_url]', function () {
    var url = $(this).value();
    if (url != '') {
        $('#img_device_preview').attr('src', url);
    }
});
</script>

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'hydrolinkhome', 'js', 'hydrolinkhome'); ?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js'); ?>