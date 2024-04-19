<template>
  <div class="main-content">
    <breadcumb :page="$t('AddProduct')" :folder="$t('Products')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <validation-observer ref="Create_Product" v-if="!isLoading">
      <b-form @submit.prevent="Submit_Product" enctype="multipart/form-data">
        <b-row>
          <b-col md="8" class="mb-2">
            <b-card>
              <b-row>
                <!-- Name -->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Name"
                    :rules="{required:true , min:3 , max:55}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Name_product') + ' ' + '*'">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="Name-feedback"
                        label="Name"
                        :placeholder="$t('Enter_Name_Product')"
                        v-model="product.name"
                      ></b-form-input>
                      <b-form-invalid-feedback id="Name-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Code Product"-->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Code Product"
                    :rules="{ required: true}"
                  >
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('CodeProduct') + ' ' + '*'">
                      <div class="input-group">
                        <b-form-input
                          :class="{'is-invalid': !!errors.length}"
                          :state="errors[0] ? false : (valid ? true : null)"
                          aria-describedby="CodeProduct-feedback"
                          type="text"
                          v-model="product.code"
                        ></b-form-input>
                        <b-form-invalid-feedback id="CodeProduct-feedback">{{ errors[0] }}</b-form-invalid-feedback>
                      </div>
                        <span>{{$t('Scan_your_barcode_and_select_the_correct_symbology_below')}}</span>
                        <b-alert
                          show
                          variant="danger"
                          class="error mt-1"
                          v-if="code_exist !=''"
                        >{{code_exist}}</b-alert>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Category -->
                <b-col md="6" class="mb-2">
                  <validation-provider name="category" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Categorie') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Category')"
                        v-model="product.category_id"
                        :options="categories.map(categories => ({label: categories.name, value: categories.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Brand  -->
                <b-col md="6" class="mb-2">
                  <b-form-group :label="$t('Brand')">
                    <v-select
                      :placeholder="$t('Choose_Brand')"
                      :reduce="label => label.value"
                      v-model="product.brand_id"
                      :options="brands.map(brands => ({label: brands.name, value: brands.id}))"
                    />
                  </b-form-group>
                </b-col>

                <!-- Barcode Symbology  -->
                <b-col md="6" class="mb-2">
                  <validation-provider name="Barcode Symbology" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('BarcodeSymbology') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="product.Type_barcode"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Symbology')"
                        :options="
                            [
                              {label: 'Code 128', value: 'CODE128'},
                              {label: 'Code 39', value: 'CODE39'},
                              {label: 'EAN8', value: 'EAN8'},
                              {label: 'EAN13', value: 'EAN13'},
                              {label: 'UPC', value: 'UPC'},
                            ]"
                      ></v-select>
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Product Cost -->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Product Cost"
                    :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('ProductCost') + ' ' + '*'">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="ProductCost-feedback"
                        label="Cost"
                        :placeholder="$t('Enter_Product_Cost')"
                        v-model="product.cost"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="ProductCost-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Product Price -->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Product Price"
                    :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('ProductPrice') + ' ' + '*'">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="ProductPrice-feedback"
                        label="Price"
                        :placeholder="$t('Enter_Product_Price')"
                        v-model="product.price"
                      ></b-form-input>

                      <b-form-invalid-feedback
                        id="ProductPrice-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Unit Product -->
                <b-col md="6" class="mb-2">
                  <validation-provider name="Unit Product" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('UnitProduct') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="product.unit_id"
                        class="required"
                        required
                        @input="Selected_Unit"
                        :placeholder="$t('Choose_Unit_Product')"
                        :reduce="label => label.value"
                        :options="units.map(units => ({label: units.name, value: units.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Unit Sale -->
                <b-col md="6" class="mb-2">
                  <validation-provider name="Unit Sale" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('UnitSale') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="product.unit_sale_id"
                        :placeholder="$t('Choose_Unit_Sale')"
                        :reduce="label => label.value"
                        :options="units_sub.map(units_sub => ({label: units_sub.name, value: units_sub.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Unit Purchase -->
                <b-col md="6" class="mb-2">
                  <validation-provider name="Unit Purchase" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('UnitPurchase') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="product.unit_purchase_id"
                        :placeholder="$t('Choose_Unit_Purchase')"
                        :reduce="label => label.value"
                        :options="units_sub.map(units_sub => ({label: units_sub.name, value: units_sub.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Stock Alert -->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Stock Alert"
                    :rules="{ regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('StockAlert')">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="StockAlert-feedback"
                        label="Stock alert"
                        :placeholder="$t('Enter_Stock_alert')"
                        v-model="product.stock_alert"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="StockAlert-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Order Tax -->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Order Tax"
                    :rules="{regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('OrderTax')">
                      <div class="input-group">
                        <input
                          :state="getValidationState(validationContext)"
                          aria-describedby="OrderTax-feedback"
                          v-model.number="product.TaxNet"
                          type="text"
                          class="form-control"
                        >
                        <div class="input-group-append">
                          <span class="input-group-text">%</span>
                        </div>
                      </div>
                      <b-form-invalid-feedback
                        id="OrderTax-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Tax Method -->
                <b-col lg="6" md="6" sm="12" class="mb-2">
                  <validation-provider name="Tax Method" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('TaxMethod') + ' ' + '*'">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="product.tax_method"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Method')"
                        :options="
                           [
                            {label: 'Exclusive', value: '1'},
                            {label: 'Inclusive', value: '2'}
                           ]"
                      ></v-select>
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <b-col md="12" class="mb-2">
                  <b-form-group :label="$t('Description')">
                    <textarea
                      rows="4"
                      class="form-control"
                      :placeholder="$t('Afewwords')"
                      v-model="product.note"
                    ></textarea>
                  </b-form-group>
                </b-col>

                 <!-- Multiple Variants -->
                  <b-col md="12 mb-2">
                    <ValidationProvider rules vid="product" v-slot="x">
                      <div class="form-check">
                        <label class="checkbox checkbox-outline-primary">
                          <input type="checkbox" v-model="product.is_variant">
                          <h5>{{$t('ProductHasMultiVariants')}}</h5>
                          <span class="checkmark"></span>
                        </label>
                      </div>
                    </ValidationProvider>
                  </b-col>
                  <b-col md="12 mb-5" v-show="product.is_variant">
                    <vue-tags-input
                      placeholder="+ add"
                      v-model="tag"
                      :tags="variants"
                      class="tag-custom text-15"
                      @adding-duplicate="showNotifDuplicate()"
                      @tags-changed="newTags => variants = newTags"
                    />
                  </b-col>

                  <!-- Product_Has_Imei_Serial_number -->
                   <b-col md="12 mb-2">
                    <ValidationProvider rules vid="product" v-slot="x">
                      <div class="form-check">
                        <label class="checkbox checkbox-outline-primary">
                          <input type="checkbox" v-model="product.is_imei">
                          <h5>{{$t('Product_Has_Imei_Serial_number')}}</h5>
                          <span class="checkmark"></span>
                        </label>
                      </div>
                    </ValidationProvider>
                  </b-col>

                  <!-- This_Product_Not_For_Selling -->
                   <b-col md="12 mb-2">
                    <ValidationProvider rules vid="product" v-slot="x">
                      <div class="form-check">
                        <label class="checkbox checkbox-outline-primary">
                          <input type="checkbox" v-model="product.not_selling">
                          <h5>{{$t('This_Product_Not_For_Selling')}}</h5>
                          <span class="checkmark"></span>
                        </label>
                      </div>
                    </ValidationProvider>
                  </b-col>
                  
              </b-row>
            </b-card>
          </b-col>


          <b-col md="4">
            <!-- upload-multiple-image -->
            <b-card>
              <div class="card-header">
                <h5>{{$t('MultipleImage')}}</h5>
              </div>
              <div class="card-body">
                <b-row class="form-group">
                  <b-col md="12 mb-5">
                    <div
                      id="my-strictly-unique-vue-upload-multiple-image"
                      class="d-flex justify-content-center"
                    >
                      <vue-upload-multiple-image
                      @upload-success="uploadImageSuccess"
                      @before-remove="beforeRemove"
                      dragText="Drag & Drop Multiple images For product"
                      dropText="Drag & Drop image"
                      browseText="(or) Select"
                      accept=image/gif,image/jpeg,image/png,image/bmp,image/jpg
                      primaryText='success'
                      markIsPrimaryText='success'
                      popupText='have been successfully uploaded'
                      :data-images="images"
                      idUpload="myIdUpload"
                      :showEdit="false"
                      />
                    </div>
                  </b-col>
                 
                </b-row>
              </div>
            </b-card>
          </b-col>
          <b-col md="12" class="mt-3">
             <b-button variant="primary" type="submit" :disabled="SubmitProcessing">{{$t('submit')}}</b-button>
              <div v-once class="typo__p" v-if="SubmitProcessing">
                <div class="spinner sm spinner-primary mt-3"></div>
              </div>
          </b-col>
        </b-row>
      </b-form>
    </validation-observer>
  </div>
</template>


<script>
import VueUploadMultipleImage from "vue-upload-multiple-image";
import VueTagsInput from "@johmun/vue-tags-input";
import NProgress from "nprogress";
import IndexedDBHelper from './IndexedDBHelper.js';

export default {
  metaInfo: {
    title: "Create Product"
  },
  data() {
    return {
      tag: "",
      len: 8,
      images: [],
      imageArray: [],
      change: false,
      isLoading: true,
      SubmitProcessing:false,
      data: new FormData(),
      categories: [],
      units: [],
      units_sub: [],
      brands: [],
      roles: {},
      variants: [],
      product: {
        name: "",
        code: "",
        Type_barcode: "",
        cost: "",
        price: "",
        brand_id: "",
        category_id: "",
        TaxNet: "0",
        tax_method: "1",
        unit_id: "",
        unit_sale_id: "",
        unit_purchase_id: "",
        stock_alert: "0",
        image: "",
        note: "",
        is_variant: false,
        is_imei: false,
        not_selling: false,
      },
      code_exist: ""
    };
  },

  components: {
    VueUploadMultipleImage,
    VueTagsInput
  },

  methods: {
    //------------- Submit Validation Create Product
    //  async Submit_Product() {
    //   this.$refs.Create_Product.validate().then(success => {
    //     if (!success) {
    //       this.makeToast(
    //         "danger",
    //         this.$t("Please_fill_the_form_correctly"),
    //         this.$t("Failed")
    //       );
    //     } else {
    //       if (navigator.onLine) {
    //         // Online: Proceed with creating the product
    //         this.Create_Product();
    //       } else {
    //         // Offline: Save the product data to localStorage
    //         const offlineProductData = {
    //           product: this.product,
    //           variants: this.variants,
    //           images: this.images,
    //         };
    //         localStorage.setItem("offlineProductData", JSON.stringify(offlineProductData));
    //         this.makeToast(
    //           "info",
    //           this.$t("Product data saved for synchronization when online."),
    //           this.$t("Offline")
    //         );
    //       }
    //     }
    //   });
    // },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    //------ Validation State
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------Show Notification If Variant is Duplicate
    showNotifDuplicate() {
      this.makeToast(
        "warning",
        this.$t("VariantDuplicate"),
        this.$t("Warning")
      );
    },

    //------ Event upload Image Success
    uploadImageSuccess(formData, index, fileList, imageArray) {
      this.images = fileList;
    },

    //------ Event before Remove Image
    beforeRemove(index, done, fileList) {
      var remove = confirm("remove image");
      if (remove == true) {
        this.images = fileList;
        done();
      } else {
      }
    },

    //-------------- Product Get Elements
    GetElements() {
      axios
        .get("products/create")
        .then(response => {
          this.categories = response.data.categories;
          this.brands = response.data.brands;
          this.units = response.data.units;
          this.isLoading = false;
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //---------------------- Get Sub Units with Unit id ------------------------------\\
    Get_Units_SubBase(value) {
      axios
        .get("get_sub_units_by_base?id=" + value)
        .then(({ data }) => (this.units_sub = data));
    },

    //---------------------- Event Select Unit Product ------------------------------\\
    Selected_Unit(value) {
      this.units_sub = [];
      this.product.unit_sale_id = "";
      this.product.unit_purchase_id = "";
      this.Get_Units_SubBase(value);
    },
    async syncDataWhenOnline() {
      if (navigator.onLine) {
        const offlineProductData = JSON.parse(localStorage.getItem("offlineProductData"));
        if (offlineProductData) {
          try {
            const response = await axios.post("products", offlineProductData);
            console.log('Data synchronized with server:', response.data);
            localStorage.removeItem("offlineProductData");
            this.makeToast(
              "success",
              this.$t("Product data synchronized with the server."),
              this.$t("Online")
            );
          } catch (error) {
            console.error('Error synchronizing data:', error);
            this.makeToast(
              "danger",
              this.$t("Error synchronizing product data with the server."),
              this.$t("Online")
            );
          }
        }
      }
    },

    //------------------------------ Create new Product ------------------------------\\
   // In your Create_Product method
async Create_Product() {
  NProgress.start();
  this.SubmitProcessing = true;

  // Append product fields to FormData for submitting to a server
  let formData = new FormData();
  Object.keys(this.product).forEach(key => {
    formData.append(key, this.product[key]);
  });

  const idbHelper = new IndexedDBHelper('ProductsDBs', 1, 'products');
  try {
    await indexedDBHelper.saveData(product);
    this.makeToast("success", "Successfully Created", "Success");
    this.$router.push({ name: "index_products" });
  } catch (error) {
    console.error("Error saving to IndexedDB", error);
    this.makeToast("error", "Save Local Failed", "Failed");
  } finally {
    NProgress.done();
    this.SubmitProcessing = false;
  }
},
async openIndexedDB() {
  if (!('indexedDB' in window)) {
    throw new Error("This browser doesn't support IndexedDB");
  }
  return await idb.openDB('ProductsDBs', 1, {
    upgrade(db) {
      if (!db.objectStoreNames.contains('products')) {
        db.createObjectStore('products', { keyPath: 'id', autoIncrement: true });
      }
    }
  });
}
,
  async Submit_Product() {
  this.$refs.Create_Product.validate().then(success => {
    if (!success) {
      this.makeToast(
        "danger",
        this.$t("Please_fill_the_form_correctly"),
        this.$t("Failed")
      );
    } else {
      if (navigator.onLine) {
        // Online: Proceed with creating the product
        this.Create_Product();
      } else {
        // Offline: Save the product data to localStorage
        const offlineProductData = {
          product: this.product,
          variants: this.variants,
          images: this.images,
        };
        console.log('products for offline:;',this.product);
        localStorage.setItem("offlineProductData", JSON.stringify(offlineProductData));
        this.makeToast(
          "info",
          this.$t("Product data saved for synchronization when online."),
          this.$t("Offline")
        );
      }
    }
  });
},

  },
  //end Methods

  //-----------------------------Created function-------------------

 created() {
    this.GetElements();
  },

  // Listen for online event and call syncDataWhenOnline
  mounted() {
    window.addEventListener("online", this.syncDataWhenOnline);
  },

  beforeDestroy() {
    window.removeEventListener("online", this.syncDataWhenOnline);
  },
};
</script>
