/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/* global FullCalendar, FullCalendarLocales */
/* global glpi_ajax_dialog */

var Reservations = function() {
   this.is_all      = true;
   this.id          = 0;
   this.rand        = '';
   this.dom_id      = '';
   this.calendar    = null;
   this.license_key = null;
   this.currentv    = null;
   this.defaultDate = null;

   var my = this;

   my.init = function(config) {
      my.id           = config.id || 0;
      my.is_all       = config.is_all || true;
      my.rand         = config.rand || true;
      my.is_tab       = config.is_tab || false;
      my.license_key  = config.license_key || '';
      my.dom_id       = "reservations_planning_"+my.rand;
      my.currentv     = config.currentv || 'dayGridMonth';
      my.defaultDate  = config.defaultDate || new Date();
      my.defaultPDate = new Date(my.defaultDate);
   };

   my.displayPlanning = function() {
      my.calendar = new FullCalendar.Calendar(document.getElementById(my.dom_id), {
         schedulerLicenseKey: my.license_key,
         timeZone: 'UTC',
         nowIndicator: true,
         theme: true,
         editable: true,
         defaultDate: my.defaultDate,
         minTime:     CFG_GLPI.planning_begin,
         maxTime:     CFG_GLPI.planning_end,
         weekNumbers: true,
         defaultView:  localStorage.getItem("fcDefaultViewReservation") !== null
            ? localStorage.getItem("fcDefaultViewReservation")
            : my.currentv,
         height: function() {
            var _newheight = $(window).height() - 272;
            if ($('#debugajax').length > 0) {
               _newheight -= $('#debugajax').height();
            }

            if (my.is_tab) {
               // TODO .glpi_tabs not exists anymore
               _newheight = $('.glpi_tabs ').height() - 150;
            }

            //minimal size
            var _minheight = 300;
            if (_newheight < _minheight) {
               _newheight = _minheight;
            }

            return _newheight;
         },
         resourceAreaWidth: '15%',
         plugins: ['dayGrid', 'interaction', 'list', 'timeGrid', 'resourceTimeline'],
         header: {
            left:   'prev,next,today',
            center: 'title',
            right:  'dayGridMonth, timeGridWeek, timeGridDay, listFull, resourceWeek'
         },

         views: {
            listFull: {
               type: 'list',
               titleFormat: function() {
                  return '';
               },
               visibleRange: function(currentDate) {
                  var current_year = currentDate.getFullYear();
                  var offset = 1;
                  if (my.id > 0) {
                     offset = 10;
                  }
                  return {
                     start: (new Date(currentDate.getTime())).setFullYear(current_year - offset),
                     end: (new Date(currentDate.getTime())).setFullYear(current_year + offset)
                  };
               }
            },
            resourceWeek: {
               type: 'resourceTimeline',
               buttonText: __('Timeline Week'),
               duration: { weeks: 1 },
               groupByDateAndResource: true,
               slotLabelFormat: [
                  { week: 'short' },
                  { weekday: 'short', day: 'numeric', month: 'numeric', omitCommas: true },
                  function(date) {
                     return date.date.hour;
                  }
               ]
            },
         },

         events: {
            url:  CFG_GLPI.root_doc+"/ajax/reservations.php",
            type: 'GET',
            extraParams: {
               'action': 'get_events',
               'reservationitems_id': my.id,
            },
            success: function() {

            },
            failure: function(error) {
               console.error('there was an error while fetching events!', error);
            }
         },

         resources: {
            url:  CFG_GLPI.root_doc+"/ajax/reservations.php",
            method: 'GET',
            extraParams: {
               'action': 'get_resources',
            }
         },

         eventRender: function(info) {
            var event    = info.event;
            var extProps = event.extendedProps;
            var element  = $(info.el);
            var view     = info.view;

            // add icon if exists
            if ("icon" in extProps && !my.is_tab) {
               var icon_alt = "";
               if ("icon_alt" in extProps) {
                  icon_alt = extProps.icon_alt;
               }

               element.find(".fc-title, .fc-list-item-title")
                  .append("&nbsp;<i class='"+extProps.icon+"' title='"+icon_alt+"'></i>");
            }

            // detect ideal position
            var qtip_position = {
               target: 'mouse',
               adjust: {
                  mouse: false
               },
               viewport: $(window)
            };
            if (view.type.indexOf('list') >= 0) {
               // on central, we want the tooltip on the anchor
               // because the event is 100% width and so tooltip will be too much on the right.
               qtip_position.target= element.find('a');
            }

            element.qtip({
               position: qtip_position,
               content: extProps.comment,
               style: {
                  classes: 'qtip-shadow qtip-bootstrap'
               },
               show: {
                  solo: true,
                  delay: 100
               },
               hide: {
                  fixed: true,
                  delay: 100
               }
            });
         },

         dayRender: function (info) {
            if (my.dateAreSameDay(info.date, my.defaultPDate)) {
               $(info.el).addClass('defaultDate');
            }
         },

         viewSkeletonRender: function (info) {
            var view = info.view;

            // when the view changes, we update our localStorage value with the new view name
            localStorage.setItem("fcDefaultViewReservation", view.type);
         },

         eventResize: function(info) {
            my.editEvent(info);
         },

         eventDrop: function(info) {
            my.editEvent(info);
         },

         // ADD EVENTS
         selectable: true,
         select: function(info) {
            glpi_ajax_dialog({
               title: __("Add reservation"),
               url: CFG_GLPI.root_doc+"/ajax/reservations.php",
               params: {
                  action: 'add_reservation_fromselect',
                  id:     my.id,
                  start:  info.start.toISOString(),
                  end:    info.end.toISOString(),
               },
               dialogclass: 'modal-lg',
            });

            my.calendar.unselect();
         },

         eventClick: function(info) {
            var event   = info.event;
            var ajaxurl = event.extendedProps.ajaxurl;

            if (ajaxurl) {
               info.jsEvent.preventDefault(); // don't let the browser navigate

               glpi_ajax_dialog({
                  title: __("Edit reservation"),
                  url: ajaxurl+"&ajax=true",
                  dialogclass: 'modal-lg',
               });
            }
         }
      });

      my.calendar.render();

      // load language
      var loadedLocales = Object.keys(FullCalendarLocales);
      if (loadedLocales.length === 1) {
         my.calendar.setOption('locale', loadedLocales[0]);
      }
   };

   // send an ajax request to update a reservation
   my.editEvent = function(info) {
      var event      = info.event;
      var revertFunc = info.revert;

      var start      = event.start;
      var end        = event.end;

      $.ajax({
         url: CFG_GLPI.root_doc+"/ajax/reservations.php",
         type: 'POST',
         data: {
            action:        'update_event',
            start:         start.toISOString(),
            end:           end.toISOString(),
            id:            event.id,
         },
         success: function(html) {
            if (!html) {
               revertFunc();
            }
         },
         error: function() {
            revertFunc();
         }
      });
   };

   my.dateAreSameDay = function(date1, date2) {
      return date1.getFullYear() === date2.getFullYear()
          && date1.getMonth() === date2.getMonth()
          && date1.getDate() === date2.getDate();
   };
};
