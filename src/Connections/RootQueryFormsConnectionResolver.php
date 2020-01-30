<?php

namespace WPGraphQLGravityForms\Connections;

use GFAPI;
use GraphQL\Error\UserError;
use GraphQLRelay\Connection\ArrayConnection;
use WPGraphQLGravityForms\DataManipulators\FieldsDataManipulator;
use WPGraphQLGravityForms\DataManipulators\FormDataManipulator;
use WPGraphQL\Data\Connection\AbstractConnectionResolver;

class RootQueryFormsConnectionResolver extends AbstractConnectionResolver {
    /**
     * @return bool Whether query should execute.
     */
    public function should_execute() : bool {
        return current_user_can( 'gravityforms_preview_forms' );
    }

    /**
     * @return array Query arguments.
     */
    public function get_query_args() : array {
        return [];
    }

    /**
     * @param array $node The node.
     * @param null  $key  Unused arg.
     *
     * @return string Base-64 encoded cursor value.
     */
    protected function get_cursor_for_node( $node, $key = null ) : string {
        return base64_encode( ArrayConnection::PREFIX . $node['formId'] );
    }

    /**
     * @return array Query to use for data fetching.
     */
    public function get_query() : array {
        return [];
    }

    /**
     * @return array The fields for this Gravity Forms form.
     */
    public function get_items() : array {
        $forms = GFAPI::get_forms(
            true,
            false
        );

        if ( is_wp_error( $forms ) ) {
            throw new UserError( __( 'An error occurred while trying to get Gravity Forms forms.', 'wp-graphql-gravity-forms' ) );
        }

        $form_data_manipulator = new FormDataManipulator(new FieldsDataManipulator);

        return array_map( function( $form ) use ( $form_data_manipulator ) {
            return $form_data_manipulator->manipulate( $form );
        }, $forms );
    }

}
