<template>
  <div>
    <div class="customizer" :class="{ open: isOpen }">
      <div class="handle" @click="isOpen = !isOpen">
        <i class="i-Gear spin"></i>
      </div>

      <vue-perfect-scrollbar
        :settings="{ suppressScrollX: true, wheelPropagation: false }"
        class="customizer-body ps rtl-ps-none"
      >
        <div
          class
          v-if="getThemeMode.layout != 'vertical-sidebar' && getThemeMode.layout != 'vertical-sidebar-two'"
        >
          <div class="card-header" id="headingOne">
            <p class="mb-0">RTL</p>
          </div>

          <div class="card-body">
            <label class="checkbox checkbox-primary">
              <input type="checkbox" id="rtl-checkbox" @change="changeThemeRtl" />
              <span>Enable RTL</span>
              <span class="checkmark"></span>
            </label>
          </div>
        </div>

        <div class>
          <div class="card-header">
            <p class="mb-0">Dark Mode</p>
          </div>

          <div class="card-body">
            <label class="switch switch-primary mr-3 mt-2" v-b-popover.hover.left="'Dark Mode'">
              <input type="checkbox" @click="changeThemeMode" />
              <span class="slider"></span>
            </label>
          </div>
        </div>
        <div class>
          <div class="card-header">
            <p class="mb-0">Connectivity Status</p>
          </div>

          <div class="card-body">
            <label class="switch switch-primary mr-3 mt-2" v-b-popover.hover.left="popoverMessage">
              <input type="checkbox" :checked="status === 'on'" @change="toggleStatus" />
              <span class="slider"></span>
            </label>
          </div>
        </div>


      </vue-perfect-scrollbar>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapActions } from "vuex";
import IndexedDBHelper from './../../../src/IndexedDBHelper.js';

export default {
  data() {
    return {
      isOpen: false,
       langs: [
        "en",

      ],
      status: 'on',
    };
  },

  computed: {
    ...mapGetters(["getThemeMode", "getcompactLeftSideBarBgColor"]),
    popoverMessage() {
      return this.status === 'on' ? 'Online Mode' : 'Offline Mode';
    },

  },

  methods: {
    ...mapActions([
      "changeThemeRtl",
      "changeThemeLayout",
      "changeThemeMode",
      "changecompactLeftSideBarBgColor",
    ]),

    SetLocal(locale) {
      this.$i18n.locale = locale;
      this.$store.dispatch("language/setLanguage", locale);
      Fire.$emit("ChangeLanguage");
    },
    popoverMessage() {
      return this.status === 'on' ? 'Online Mode' : 'Offline Mode';
    },
    async toggleStatus(event) {
  const newStatus = event.target.checked ? 'on' : 'off';
  this.status = newStatus;
  
  const indexedDBHelper = new IndexedDBHelper('ProductsDBs', 2);
  await indexedDBHelper.saveDataStatus('status', { id: 1, status: newStatus });
},

  },
  async mounted() {
  const indexedDBHelper = new IndexedDBHelper('ProductsDBs', 2);
  const data = await indexedDBHelper.getData('status');
  
  if (data.length > 0) {
    this.status = data[0].status;
  } else {
    this.status = 'on'; // Default to 'off' if no data
  }

}
};
</script>

<style lang="scss" scoped>
</style>
