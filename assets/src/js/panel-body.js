/* global autopagingSettings */

import { ToggleControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const { metaKey } = autopagingSettings;

/**
 * Render controls.
 *
 * @param {Object}   props             Component props.
 * @param {boolean}  props.disabled    Whether autopaging is disabled.
 * @param {boolean}  props.hasQuicktag Whether post is manually paginated.
 * @param {Function} props.setDisabled Save updated setting.
 * @return {JSX.Element} Panel body.
 */
const View = ( { disabled, hasQuicktag, setDisabled } ) => (
	<>
		{ hasQuicktag && (
			<p
				dangerouslySetInnerHTML={ {
					__html: __(
						'Autopaging is disabled because the <em>Page Break</em> block is used.',
						'autopaging'
					),
				} }
			/>
		) }

		{ ! hasQuicktag && (
			<>
				<ToggleControl
					label={ __(
						'Disable autopaging for this post?',
						'autopaging'
					) }
					help={ __(
						'Check the box above to prevent this post from automatically being split over multiple pages.',
						'autopaging'
					) }
					checked={ disabled }
					onChange={ setDisabled }
				/>
			</>
		) }
	</>
);

/**
 * HOC to provide meta values and methods for updating meta.
 */
const PanelBody = compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );
		const content = getEditedPostAttribute( 'content' );
		const meta = getEditedPostAttribute( 'meta' );

		return {
			disabled: !! meta[ metaKey ],
			hasQuicktag: -1 !== content.indexOf( 'wp:nextpage' ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { editPost } = dispatch( 'core/editor' );

		const setDisabled = ( target ) => {
			editPost( {
				meta: {
					[ metaKey ]: !! target,
				},
			} );
		};

		return {
			setDisabled,
		};
	} ),
] )( View );

export default PanelBody;
