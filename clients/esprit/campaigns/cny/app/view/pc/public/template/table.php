<?php
class TableTemplate {

	private $pageData;

	public function __construct (array $pageData) {
		$this->pageData = $pageData;
	}

	public function makeHtml () {
		$pageData = $this->pageData;
	?>

		<table<?=empty($pageData['table_id']) ? '' : (' id="'.$pageData['table_id'].'"')?>>
			<thead>

		<?php
			foreach ($pageData['head'] as $row) {
		?>

				<tr>

		<?php
				foreach ($row as $content) {
					$type = 'td';
					if (isset($content['type']) && $content['type'] == 'header') {
						$type = 'th';
					}
		?>

					<<?=$type?> rowspan="<?=$content['rows']?>" colspan="<?=$content['cols']?>"<?=empty($content['width']) ? '' : (' width="'.$content['width'].'"')?>><?=$content['text']?></<?=$type?>>

		<?php
				}
		?>

				</tr>

		<?php
			}
		?>

			</thead>
			<tbody>

		<?php
			if (!empty($pageData['body'])) {
				foreach ($pageData['body'] as $row) {
		?>

				<tr>

		<?php
					foreach ($row as $content) {
						$type = 'td';
						if (isset($content['type']) && $content['type'] == 'header') {
							$type = 'th';
						}
		?>

					<<?=$type?> rowspan="<?=$content['rows']?>" colspan="<?=$content['cols']?>"<?=empty($content['width']) ? '' : (' width="'.$content['width'].'"')?>><?=$content['text']?></<?=$type?>>

		<?php
					}
		?>

				</tr>

		<?php
				}
			}
		?>

			</tbody>
			<tbody class="new_row">
			</tbody>
		</table>

		<script type="text/javascript">
			if (typeof Table == typeof undefined) {
				Table = function (table_id) {
					this.table_id = table_id;
				};
				Table.prototype = $.extend(new Table(), {
					constructor	: Table,
					addNewRow	: function () {
						var newRowContainer = $('table#' + this.table_id + ' .new_row');
						if (newRowContainer.html().trim().length == 0) {
							var row = $('<tr><?php
								foreach ($pageData['head'][0] as $col) {
									echo '<td></td>';
								}
							?></tr>');
							newRowContainer.append(row);
						}
						return this;
					}
				});
			}
		</script>

	<?php
	}
}
?>
