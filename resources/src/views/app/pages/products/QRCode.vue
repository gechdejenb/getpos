<template>
  <div class="main-content">
    <!-- ... Your existing template code ... -->

    <!-- QR Code Section -->
    <div class="qr-code-section">
      <button @click="generateQRCode" class="btn btn-primary btn-sm m-1">
        Generate QR Code
      </button>
      <img v-if="qrCodeImageUrl" :src="qrCodeImageUrl" alt="QR Code" />
    </div>
  </div>
</template>

<script>
import VueBarcode from "vue-barcode";
import NProgress from "nprogress";
import QRCode from "qrcode"; // Import QRCode library

export default {
  components: {
    barcode: VueBarcode
  },
  data() {
    return {
      focused: false,
      timer: null,
      search_input: "",
      product_filter: [],
      isLoading: true,
      ShowCard: false,
      barcode: {
        product_id: "",
        warehouse_id: "",
        qte: 10
      },
      count: "",
      paper_size: "",
      sheets: "",
      total_a4: "",
      class_sheet: "",
      class_type_page: "",
      rest: "",
      warehouses: [],
      submitStatus: null,
      products: [],
      product: {
        name: "",
        code: "",
        Type_barcode: "",
        barcode: ""
      },
      qrCodeImageUrl: "" // To store the generated QR code image URL
    };
  },
  methods: {
    // ... Your existing methods ...

    // Add the generateQRCode method
    generateQRCode() {
      const qrData = {
        company: "Your Company Name",
        website: "https://www.yourwebsite.com",
        address: "123 Main St, City",
        product: "Product Name",
        expiration: "2023-12-31"
      };

      const qrText = JSON.stringify(qrData);

      QRCode.toDataURL(qrText, (error, url) => {
        if (error) {
          console.error("QR Code generation error:", error);
        } else {
          this.qrCodeImageUrl = url;
        }
      });
    },

    // ... Your existing methods ...
  },

  // ... The rest of your script ...
};
</script>

<!-- ... Your existing style ... -->
