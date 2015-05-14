YUI.add('moodle-atto_fontcolor-button', function (Y, NAME) {

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    atto_fontcolor
 * @copyright  2014 Rossiani Wijaya  <rwijaya@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_align-button
 */

/**
 * Atto text editor fontcolor plugin.
 *
 * @namespace M.atto_fontcolor
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */

var colors = [
        {
            name: 'white',
            color: '#FFFFFF'
        }, {
            name: 'red',
            color: '#EF4540'
        }, {
            name: 'yellow',
            color: '#FFCF35'
        }, {
            name: 'green',
            color: '#98CA3E'
        }, {
            name: 'blue',
            color: '#7D9FD3'
        }, {
            name: 'black',
            color: '#333333'
        },
        {
            name: 'blue',
            color: '#006699'
        },
        {
                name: 'orange',
                color: '#ff7f50'
        },
        {
                name: 'purple',
                color: '#000080'
        },
        {
                name: 'cyan',
                color: '#00bfff'
        },
        {
                name: 'red',
                color: '#ff0000'
        },
        {
                name: 'gray',
                color: '#888888'
        },
        {
                name: 'green',
                color: '#008000'
        },
        {
                name: 'dark red',
                color: '#800000'
        },
        {
                name: 'indigo',
                color: '#333399'
        },
        {
                name: 'brandons favorite red',
                color: '#cc0000'
        },
        {
                name: 'purple alternate #1',
                color: '#9400d3'
        },
        {
                name: 'orange alternate #1',
                color: '#ff6600'
        },
        {
                name: 'seagreen',
                color: '#008080'
        },
        {
                name: 'ugly yellow',
                color: '#daa520'
        },
        {
                name: 'sky blue',
                color: '#4682b4'
        },
        {
                name: 'pink',
                color: '#c71585'
        },
        {
                name: 'gray alternate #1',
                color: '#666666'
        },
        {
                name: 'blue alternate #1',
                color: '#1e90ff'
        },
        {
                name: '000000',
                color: '#000000'
        },
        {
                name: '993300',
                color: '#993300'
        },
        {
                name: '333300',
                color: '#333300'
        },
        {
                name: '003300',
                color: '#003300'
        },
        {
                name: '003366',
                color: '#003366'
        },
        {
                name: '000080',
                color: '#000080'
        },
        {
                name: '333399',
                color: '#333399'
        },
        {
                name: '333333',
                color: '#333333'
        },
        {
                name: '800000',
                color: '#800000'
        },
        {
                name: 'ff6600',
                color: '#ff6600'
        },
        {
                name: '808000',
                color: '#808000'
        },
        {
                name: '008000',
                color: '#008000'
        },
        {
                name: '008080',
                color: '#008080'
        },
        {
                name: '0000ff',
                color: '#0000ff'
        },
        {
                name: '666699',
                color: '#666699'
        },
        {
                name: '808080',
                color: '#808080'
        },
        {
                name: 'ff0000',
                color: '#ff0000'
        },
        {
                name: 'ff9900',
                color: '#ff9900'
        },
        {
                name: '99cc00',
                color: '#99cc00'
        },
        {
                name: '339966',
                color: '#339966'
        },
        {
                name: '33cccc',
                color: '#33cccc'
        },
        {
                name: '3366ff',
                color: '#3366ff'
        },
        {
                name: '800080',
                color: '#800080'
        },
        {
                name: '999999',
                color: '#999999'
        },
        {
                name: 'ff00ff',
                color: '#ff00ff'
        },
        {
                name: 'ffcc00',
                color: '#ffcc00'
        },
        {
                name: '00ff00',
                color: '#00ff00'
        },
        {
                name: '00ffff',
                color: '#00ffff'
        },
        {
                name: '00ccff',
                color: '#00ccff'
        },
        {
                name: '993366',
                color: '#993366'
        },
        {
                name: 'c0c0c0',
                color: '#c0c0c0'
        },
        {
                name: 'ff99cc',
                color: '#ff99cc'
        },
        {
                name: 'ffcc99',
                color: '#ffcc99'
        },
        {
                name: 'ffff99',
                color: '#ffff99'
        },
        {
                name: 'ccffcc',
                color: '#ccffcc'
        },
        {
                name: 'ccffff',
                color: '#ccffff'
        },
        {
                name: '99ccff',
                color: '#99ccff'
        },
        {
                name: 'cc99ff',
                color: '#cc99ff'
        },
        {
                name: 'ffffff',
                color: '#ffffff'
        },
        {
                name: '003366',
                color: '#003366'
        },
        {
                name: 'ff7f50',
                color: '#ff7f50'
        },
        {
                name: '00bfff',
                color: '#00bfff'
        },
        {
                name: '888888',
                color: '#888888'
        },
        {
                name: 'cc0000',
                color: '#cc0000'
        },
        {
                name: '9400d3',
                color: '#9400d3'
        },
        {
                name: 'daa520',
                color: '#daa520'
        },
        {
                name: '4682b4',
                color: '#4682b4'
        },
        {
                name: 'c71585',
                color: '#c71585'
        },
        {
                name: '666666',
                color: '#666666'
        },
        {
                name: '1e90ff',
                color: '#1e90ff'
        }
    ];

Y.namespace('M.atto_fontcolor').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    initializer: function() {
        var items = [];
        Y.Array.each(colors, function(color) {
            items.push({
                text: '<div style="width: 20px; height: 20px; border: 1px solid #CCC; background-color: ' +
                        color.color +
                        '"></div>',
                callbackArgs: color.color,
                callback: this._changeStyle
            });
        });

        this.addToolbarMenu({
            icon: 'e/text_color',
            overlayWidth: '4',
            menuColor: '#333333',
            globalItemConfig: {
                callback: this._changeStyle
            },
            items: items
        });
    },

    /**
     * Change the font color to the specified color.
     *
     * @method _changeStyle
     * @param {EventFacade} e
     * @param {string} color The new font color
     * @private
     */
    _changeStyle: function(e, color) {
        this.get('host').formatSelectionInlineStyle({
            color: color
        });
    }
});


}, '@VERSION@');
