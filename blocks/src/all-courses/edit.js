/**
 * Edit function for "all-courses"
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { TextControl, PanelBody } from '@wordpress/components';

function Edit({ attributes, setAttributes }) {
    const { limit } = attributes
    const blockProps = useBlockProps({ className: "pmpro-block-element"});

    return [
        <InspectorControls>
            <PanelBody>
                <TextControl
                    label={__('Limit', 'pmpro-courses')}
                    value={limit}
                    onChange={(newLimit) => setAttributes({ limit: newLimit } ) }
                    help={__('How many courses should be displayed. Set the value to -1 to show all courses.', 'pmpro-courses')}
            />
            </PanelBody>
        </InspectorControls>,
        <div {...blockProps}>
            <span className="pmpro-block-title">{__('Paid Memberships Pro Courses', 'pmpro-courses')}</span>
            <span className="pmpro-block-subtitle">{__('All Courses', 'pmpro-courses')}</span>
        </div>
    ];
}

export default Edit;