import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { pageBreak } from '@wordpress/icons';
import { registerPlugin } from '@wordpress/plugins';

import PanelBody from './panel-body';

const slug = 'autopaging';

/**
 * Render panel view.
 *
 * @return {JSX.Element|null} Sidebar panel.
 */
const View = () => (
	<PluginDocumentSettingPanel
		name={ slug }
		title={ __( 'Autopaging', 'autopaging' ) }
		className={ slug }
	>
		<PanelBody />
	</PluginDocumentSettingPanel>
);

registerPlugin( slug, {
	icon: pageBreak,
	render: View,
} );
