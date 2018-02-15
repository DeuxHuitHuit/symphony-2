<?php

/**
 * @package toolkit
 */
/**
 * The `SectionManager` is responsible for managing all Sections in a Symphony
 * installation by exposing basic CRUD operations. Sections are stored in the
 * database in `tbl_sections`.
 */

class SectionManager
{
    /**
     * An array of all the objects that the Manager is responsible for.
     *
     * @var array
     *   Defaults to an empty array.
     */
    protected static $_pool = array();

    /**
     * Takes an associative array of Section settings and creates a new
     * entry in the `tbl_sections` table, returning the ID of the Section.
     * The ID of the section is generated using auto_increment and returned
     * as the Section ID.
     *
     * @param array $settings
     *    An associative of settings for a section with the key being
     *    a column name from `tbl_sections`
     * @throws DatabaseException
     * @return integer
     *    The newly created Section's ID on success, 0 otherwise
     */
    public static function add(array $settings)
    {
        $defaults = array();
        $defaults['creation_date'] = $defaults['modification_date'] = DateTimeObj::get('Y-m-d H:i:s');
        $defaults['creation_date_gmt'] = $defaults['modification_date_gmt'] = DateTimeObj::getGMT('Y-m-d H:i:s');
        $defaults['author_id'] = 1;
        $defaults['modification_author_id'] = 1;
        $settings = array_replace($defaults, $settings);
        $inserted = Symphony::Database()
            ->insert('tbl_sections')
            ->values($settings)
            ->execute()
            ->success();

        return $inserted ? Symphony::Database()->getInsertID() : 0;
    }

    /**
     * Updates an existing Section given it's ID and an associative
     * array of settings. The array does not have to contain all the
     * settings for the Section as there is no deletion of settings
     * prior to updating the Section
     *
     * @param integer $section_id
     *    The ID of the Section to edit
     * @param array $settings
     *    An associative of settings for a section with the key being
     *    a column name from `tbl_sections`
     * @throws DatabaseException
     * @return boolean
     */
    public static function edit($section_id, array $settings)
    {
        $defaults = array();
        $defaults['modification_date'] = DateTimeObj::get('Y-m-d H:i:s');
        $defaults['modification_date_gmt'] = DateTimeObj::getGMT('Y-m-d H:i:s');
        $defaults['author_id'] = 1;
        $defaults['modification_author_id'] = 1;
        $settings = array_replace($defaults, $settings);
        return Symphony::Database()
            ->update('tbl_sections')
            ->set($settings)
            ->where(['id' => $section_id])
            ->execute()
            ->success();
    }

    /**
     * Deletes a Section by Section ID, removing all entries, fields, the
     * Section and any Section Associations in that order
     *
     * @param integer $section_id
     *    The ID of the Section to delete
     * @throws DatabaseException
     * @throws Exception
     * @return boolean
     *    Returns true when completed
     */
    public static function delete($section_id)
    {
        $details = Symphony::Database()
            ->select(['sortorder'])
            ->from('tbl_sections')
            ->where(['id' => $section_id])
            ->execute()
            ->next();

        // Delete all the entries
        $entries = Symphony::Database()
            ->select(['id'])
            ->from('tbl_entries')
            ->where(['section_id' => $section_id])
            ->execute()
            ->column('id');
        EntryManager::delete($entries);

        // Delete all the fields
        $fields = FieldManager::fetch(null, $section_id);

        if (is_array($fields) && !empty($fields)) {
            foreach ($fields as $field) {
                FieldManager::delete($field->get('id'));
            }
        }

        // Delete the section
        Symphony::Database()
            ->delete('tbl_sections')
            ->where(['id' => $section_id])
            ->execute()
            ->success();

        // Update the sort orders
        Symphony::Database()
            ->update('tbl_sections')
            ->set(['sortorder' => '$sortorder - 1'])
            ->where(['sortorder' => ['>' => $details['sortorder']]])
            ->execute();

        // Delete the section associations
        Symphony::Database()
            ->delete('tbl_sections_association')
            ->where(['or' => [
                'parent_section_id' => $section_id,
                'child_section_id' => $section_id,
            ]])
            ->execute();

        return true;
    }

    /**
     * Returns a Section object by ID, or returns an array of Sections
     * if the Section ID was omitted. If the Section ID is omitted, it is
     * possible to sort the Sections by providing a sort order and sort
     * field. By default, Sections will be order in ascending order by
     * their name
     *
     * @param integer|array $section_id
     *    The ID of the section to return, or an array of ID's. Defaults to null
     * @param string $order
     *    If `$section_id` is omitted, this is the sortorder of the returned
     *    objects. Defaults to ASC, other options id DESC
     * @param string $sortfield
     *    The name of the column in the `tbl_sections` table to sort
     *    on. Defaults to name
     * @throws DatabaseException
     * @return Section|array
     *    A Section object or an array of Section objects
     */
    public static function fetch($section_id = null, $order = 'ASC', $sortfield = 'name')
    {
        $returnSingle = false;
        $section_ids = array();

        if (!is_null($section_id)) {
            if (!is_array($section_id)) {
                $returnSingle = true;
                $section_ids = array($section_id);
            } else {
                $section_ids = $section_id;
            }
        }

        if ($returnSingle && isset(self::$_pool[$section_id])) {
            return self::$_pool[$section_id];
        }

        $sql = Symphony::Database()
            ->select()
            ->from('tbl_sections', 's');

        // Ensure they are always an ID
        $section_ids = array_map('intval', $section_ids);

        if (!empty($section_id)) {
            $sql->where(['s.id' => ['in' => $section_ids]]);
        }
        else {
            $sql->orderBy([$sortfield => $order]);
        }

        $result = $sql->execute();

        if (!$result->rowCount()) {
            return ($returnSingle ? false : array());
        }

        $ret = array();

        while ($s = $result->next()) {
            $obj = self::create();

            foreach ($s as $name => $value) {
                $obj->set($name, $value);
            }

            $obj->set('creation_date', DateTimeObj::get('c', $obj->get('creation_date')));

            $modDate = $obj->get('modification_date');
            if (!empty($modDate)) {
                $obj->set('modification_date', DateTimeObj::get('c', $obj->get('modification_date')));
            } else {
                $obj->set('modification_date', $obj->get('creation_date'));
            }

            self::$_pool[$obj->get('id')] = $obj;

            $ret[] = $obj;
        }

        return (count($ret) == 1 && $returnSingle ? $ret[0] : $ret);
    }

    /**
     * Return a Section ID by the handle
     *
     * @param string $handle
     *  The handle of the section
     * @return integer
     *  The Section ID
     */
    public static function fetchIDFromHandle($handle)
    {
        return (int)Symphony::Database()
            ->select(['id'])
            ->from('tbl_sections')
            ->where(['handle' => $handle])
            ->limit(1)
            ->execute()
            ->variable('id');
    }

    /**
     * Work out the next available sort order for a new section
     *
     * @return integer
     *  Returns the next sort order
     */
    public static function fetchNextSortOrder()
    {
        $next = (int)Symphony::Database()
            ->select(['MAX(sortorder)'])
            ->from('tbl_sections')
            ->execute()
            ->variable(0);

        return $next + 1;
    }

    /**
     * Returns a new Section object, using the SectionManager
     * as the Section's $parent.
     *
     * @return Section
     */
    public static function create()
    {
        $obj = new Section;
        return $obj;
    }

    /**
     * Create an association between a section and a field.
     *
     * @since Symphony 2.3
     * @param integer $parent_section_id
     *    The linked section id.
     * @param integer $child_field_id
     *    The field ID of the field that is creating the association
     * @param integer $parent_field_id (optional)
     *    The field ID of the linked field in the linked section
     * @param boolean $show_association (optional)
     *    Whether of not the link should be shown on the entries table of the
     *    linked section. This defaults to true.
     * @throws DatabaseException
     * @throws Exception
     * @return boolean
     *    true if the association was successfully made, false otherwise.
     */
    public static function createSectionAssociation($parent_section_id = null, $child_field_id = null, $parent_field_id = null, $show_association = true, $interface = null, $editor = null)
    {
        if (is_null($parent_section_id) && (is_null($parent_field_id) || !$parent_field_id)) {
            return false;
        }

        if (is_null($parent_section_id)) {
            $parent_field = FieldManager::fetch($parent_field_id);
            $parent_section_id = $parent_field->get('parent_section');
        }

        $child_field = FieldManager::fetch($child_field_id);
        $child_section_id = $child_field->get('parent_section');

        $fields = array(
            'parent_section_id' => $parent_section_id,
            'parent_section_field_id' => $parent_field_id,
            'child_section_id' => $child_section_id,
            'child_section_field_id' => $child_field_id,
            'hide_association' => ($show_association ? 'no' : 'yes'),
            'interface' => $interface,
            'editor' => $editor
        );

        return Symphony::Database()
            ->insert('tbl_sections_association')
            ->values($fields)
            ->execute()
            ->success();
    }

    /**
     * Permanently remove a section association for this field in the database.
     *
     * @since Symphony 2.3
     * @param integer $field_id
     *    the field ID of the linked section's linked field.
     * @throws DatabaseException
     * @return boolean
     */
    public static function removeSectionAssociation($field_id)
    {
        return Symphony::Database()
            ->delete('tbl_sections_association')
            ->where(['or' => [
                'child_section_field_id' => $field_id,
                'parent_section_field_id' => $field_id
            ]])
            ->execute()
            ->success();
    }

    /**
     * Returns the association settings for the given field id. This is to be used
     * when configuring the field so we can correctly show the association setting
     * the UI.
     *
     * @since Symphony 2.6.0
     * @param integer $field_id
     * @return string
     *  Either 'yes' or 'no', 'yes' meaning display the section.
     */
    public static function getSectionAssociationSetting($field_id)
    {
        $value = Symphony::Database()
            ->select(['hide_association'])
            ->from('tbl_sections_association')
            ->where(['child_section_field_id' => $field_id])
            ->execute()
            ->variable('hide_association');

        // We must inverse the setting. The database stores 'hide', whereas the UI
        // refers to 'show'. Hence if the database says 'yes', it really means, hide
        // the association. In the UI, this needs to be flipped to 'no' so the checkbox
        // won't be checked.
        return $value == 'no' ? 'yes' : 'no';
    }

    /**
     * Returns any section associations this section has with other sections
     * linked using fields. Has an optional parameter, `$respect_visibility` that
     * will only return associations that are deemed visible by a field that
     * created the association. eg. An articles section may link to the authors
     * section, but the field that links these sections has hidden this association
     * so an Articles column will not appear on the Author's Publish Index
     *
     * @deprecated This function will be removed in Symphony 3.0. Use `fetchChildAssociations` instead.
     * @since Symphony 2.3
     * @param integer $section_id
     *  The ID of the section
     * @param boolean $respect_visibility
     *  Whether to return all the section associations regardless of if they
     *  are deemed visible or not. Defaults to false, which will return all
     *  associations.
     * @return array
     */
    public static function fetchAssociatedSections($section_id, $respect_visibility = false)
    {
        if (Symphony::Log()) {
            Symphony::Log()->pushDeprecateWarningToLog('SectionManager::fetchAssociatedSections()', 'SectionManager::fetchChildAssociations()');
        }
        self::fetchChildAssociations($section_id, $respect_visibility);
    }

    /**
     * Returns any section associations this section has with other sections
     * linked using fields, and where this section is the parent in the association.
     * Has an optional parameter, `$respect_visibility` that
     * will only return associations that are deemed visible by a field that
     * created the association. eg. An articles section may link to the authors
     * section, but the field that links these sections has hidden this association
     * so an Articles column will not appear on the Author's Publish Index
     *
     * @since Symphony 2.3.3
     * @param integer $section_id
     *    The ID of the section
     * @param boolean $respect_visibility
     *    Whether to return all the section associations regardless of if they
     *    are deemed visible or not. Defaults to false, which will return all
     *    associations.
     * @throws DatabaseException
     * @return array
     */
    public static function fetchChildAssociations($section_id, $respect_visibility = false)
    {
        $sql = Symphony::Database()
            ->select()
            ->from('tbl_sections_association', 'sa')
            ->join('tbl_sections', 's')
            ->on(['s.id' => '$sa.child_section_id'])
            ->where(['sa.parent_section_id' => $section_id])
            ->orderBy(['s.sortorder' => 'ASC']);

        if ($respect_visibility) {
            $sql->where(['sa.hide_association' => 'no']);
        }

        return $sql->execute()->rows();
    }

    /**
     * Returns any section associations this section has with other sections
     * linked using fields, and where this section is the child in the association.
     * Has an optional parameter, `$respect_visibility` that
     * will only return associations that are deemed visible by a field that
     * created the association. eg. An articles section may link to the authors
     * section, but the field that links these sections has hidden this association
     * so an Articles column will not appear on the Author's Publish Index
     *
     * @since Symphony 2.3.3
     * @param integer $section_id
     *    The ID of the section
     * @param boolean $respect_visibility
     *    Whether to return all the section associations regardless of if they
     *    are deemed visible or not. Defaults to false, which will return all
     *    associations.
     * @throws DatabaseException
     * @return array
     */
    public static function fetchParentAssociations($section_id, $respect_visibility = false)
    {
        $sql = Symphony::Database()
            ->select()
            ->from('tbl_sections_association', 'sa')
            ->join('tbl_sections', 's')
            ->on(['s.id' => '$sa.parent_section_id'])
            ->where(['sa.child_section_id' => $section_id])
            ->orderBy(['s.sortorder' => 'ASC']);

        if ($respect_visibility) {
            $sql->where(['sa.hide_association' => 'no']);
        }

        return $sql->execute()->rows();
    }
}
