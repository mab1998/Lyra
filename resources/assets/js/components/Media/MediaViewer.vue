<template>
  <div class="pt-3 h-100">
    <lyra-loader v-if="isLoaderEnabled" class="py-5"></lyra-loader>
    <template v-else>
      <div class="row m-0">
        <div v-for="element in elements" class="element" :title="element.name"
             @contextmenu.prevent="openContextElement($event, element)">

          <template v-if="/^image\/\w+$/.test(element.mime)">
            <image-file :element="element" :selectedElements="selectedElements"
                        @drag-start="dragStart(element)" @drag-end="dragEnd()"
                        @select-element="selectElement(element)" @clear-selection="clearSelection"></image-file>
          </template>

          <template v-else-if="/^video\/\w+$/.test(element.mime)">
            <video-file :element="element" :selectedElements="selectedElements"
                        @drag-start="dragStart(element)" @drag-end="dragEnd()"
                        @select-element="selectElement(element)" @clear-selection="clearSelection"></video-file>
          </template>

          <template v-else-if="/^audio\/\w+$/.test(element.mime)">
            <audio-file :element="element" :selectedElements="selectedElements"
                        @drag-start="dragStart(element)" @drag-end="dragEnd()"
                        @select-element="selectElement(element)" @clear-selection="clearSelection"></audio-file>
          </template>

          <template v-else-if="/^directory$/.test(element.mime)">
            <directory :element="element" :selectedElements="selectedElements"
                       @change-path="$emit('change-path', element.path)"
                       @drag-start="dragStart(element)" @drag-end="dragEnd()"
                       @drop-element-move="moveDraggedElements(element.path)"
                       @drop-element-copy="copyDraggedElements(element.path)"
                       @select-element="selectElement(element)" @clear-selection="clearSelection"></directory>
          </template>

          <template v-else>
            <generic-file :element="element" :selectedElements="selectedElements"
                          @drag-start="dragStart(element)" @drag-end="dragEnd()"
                          @select-element="selectElement(element)" @clear-selection="clearSelection"></generic-file>
          </template>

        </div>
      </div>

      <div class="h-100 w-100 media-backdrop" @contextmenu.prevent="openContextGeneric($event)"
           @click.exact="clearSelection" @drop.prevent="uploadDroppedFile" @dragover.prevent></div>

      <preview-modal :element="previewElement" ref="previewModal"
                     :key="previewElement.path" v-if="previewElement"></preview-modal>

      <context-menu-elements :element="contextElement" :folder-tree="folderTree"
                             @reload-viewer="loadViewer"></context-menu-elements>
      <context-menu-selected :elements="selectedElements" :folder-tree="folderTree"
                             @reload-viewer="loadViewer"></context-menu-selected>
      <context-menu-generic @reload-viewer="loadViewer"></context-menu-generic>

    </template>
  </div>
</template>

<script>
  import ImageFile from './FileTypes/ImageFile'
  import VideoFile from './FileTypes/VideoFile'
  import AudioFile from './FileTypes/AudioFile'
  import GenericFile from './FileTypes/GenericFile'
  import Directory from './FileTypes/Directory'

  import PreviewModal from './Modals/PreviewModal'
  import DetailsModal from './Modals/DetailsModal'

  import ContextMenuElements from './ContextMenu/ContextMenuElements'
  import ContextMenuSelected from './ContextMenu/ContextMenuSelected'
  import ContextMenuGeneric from './ContextMenu/ContextMenuGeneric'

  export default {
    components: {
      PreviewModal, DetailsModal,
      ContextMenuElements, ContextMenuGeneric, ContextMenuSelected,
      ImageFile, VideoFile, AudioFile, GenericFile, Directory
    },
    props: ['folderTree'],
    data() {
      return {
        elements: null,
        previewElement: null,
        contextElement: null,
        contextMenu: null,
        selectedElements: [],
        draggedElements: [],
      }
    },
    methods: {
      getElementsFolder: function () {
        const path = this.$route.fullPath.split('?')[0];
        const query = this.$route.fullPath.split('?')[1];
        this.$http.get(`${path}/files?${query}`).then((response) => this.elements = response.data);
      },
      openContextElement: function (e, element) {
        this.removePreviousContextMenu();
        if (this.selectedElements.length) return this.openContextSelected(e);
        this.contextMenu = $('#contextMenuElement');
        this.contextElement = element;
        this.mountContextMenu(e);
      },
      openContextGeneric: function (e) {
        this.removePreviousContextMenu();
        if (this.selectedElements.length) return this.openContextSelected(e);
        this.contextMenu = $('#contextMenu');
        this.mountContextMenu(e);
      },
      openContextSelected: function (e) {
        this.contextMenu = $('#contextMenuSelected');
        this.mountContextMenu(e);
      },
      removePreviousContextMenu: function () {
        if (this.contextMenu) {
          this.contextMenu.hide();
          $(this.contextMenu).removeClass("dropup");
          this.contextMenu = null;
        }
      },
      mountContextMenu: function (e) {
        this.contextMenu[0].style.position = 'absolute';
        this.contextMenu[0].style.left = `${e.clientX}px`;
        this.contextMenu[0].style.top = `${e.clientY}px`;

        this.contextMenu.appendTo('body').show();

        let $ul = $(this.contextMenu).children(".dropdown-menu");
        let ulOffset = $ul.offset();

        // how much space would be left on the top if the dropdown opened that direction
        let spaceUp = (ulOffset.top - $ul.height()) - $(window).scrollTop();

        // how much space is left at the bottom
        let spaceDown = $(window).scrollTop() + $(window).height() - (ulOffset.top + $ul.height());

        // switch to dropup only if there is no space at the bottom AND there is space at the top,
        // or there isn't either but it would be still better fit
        if (spaceDown < 0 && (spaceUp >= 0 || spaceUp > spaceDown)) $(this.contextMenu).addClass("dropup");
      },
      uploadDroppedFile: function (e) {
        let items = e.dataTransfer.items;
        if (!items) return false;
        ([...items]).forEach(item => {
          let entry = item.webkitGetAsEntry();
          if (entry) this.uploadDroppedFolder(entry);
        });
      },
      uploadDroppedFolder: function (item, path = "") {
        if (item.isFile) {
          item.file(file => {
            let formData = new FormData();

            formData.append(`file-0`, file);
            formData.append('path', this.$route.query.path ? this.$route.query.path + '/' + path : '/' + path);
            formData.append('disk', this.$route.query.disk ? this.$route.query.disk : false);

            this.$http.post(`${this.$route.path}/upload`, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then(response => {
              if (response.status === 200) {
                toastr.success(`File ${file.name} uploaded successfully`);
                this.loadViewer();
              }
            })
          });
        } else if (item.isDirectory) {
          let dirReader = item.createReader();
          dirReader.readEntries(entries => {
            ([...entries]).forEach(entry => {
              this.uploadDroppedFolder(entry, path + item.name + "/")
            });
          });
        }
      },
      dragStart: function (element) {
        if (this.selectedElements.length) {
          let fount = this.selectedElements.find(el => el.path === element.path);
          if (fount) {
            this.draggedElements.push(...this.selectedElements);
          } else {
            this.draggedElements.push(element);
          }
        } else {
          this.draggedElements.push(element);
        }
      },
      dragEnd: function () {
        this.draggedElements = [];
      },
      moveDraggedElements: function (path) {
        this.draggedElements.forEach(element => {
          if (element.path === path) return null;
          this.$http.post(`${this.$route.path}/move`, {
            element: element,
            newPath: path,
            disk: this.$route.query.disk
          }).then(response => this.loadViewer());
        });
      },
      copyDraggedElements: function (path) {
        this.draggedElements.forEach(element => {
          if (element.path === path) return null;
          this.$http.post(`${this.$route.path}/copy`, {
            element: element,
            newPath: path,
            disk: this.$route.query.disk
          }).then(response => this.loadViewer());
        });
      },
      loadViewer: function () {
        this.$emit('reload-manager');
        this.elements = null;
        this.getElementsFolder();
      },
      selectElement: function (element) {
        let fount = this.selectedElements.find(el => el.path === element.path);
        if (!fount) {
          this.selectedElements.push(element);
        } else {
          this.selectedElements.splice(this.selectedElements.indexOf(fount), 1);
        }
      },
      clearSelection: function () {
        this.selectedElements = [];
      }
    },
    beforeMount() {
      this.getElementsFolder();
    },
    mounted() {
      $('body > #contextMenu').remove();
      $('body > #contextMenuElement').remove();
      $('body').click(() => {
        this.removePreviousContextMenu()
      });
    },
    computed: {
      isLoaderEnabled: function () {
        return (this.$root.loader === false && !this.elements && !this.folderTree)
      },
    },
  }
</script>

<style scoped>

</style>
