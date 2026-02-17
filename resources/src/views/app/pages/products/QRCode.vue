<template>
  <div class="main-content">
    <!-- Form for User Input -->
    <form @submit.prevent="generateQRCode">
      <div class="form-group">
        <label for="website">Website:</label>
        <input type="text" class="form-control" placeholder="Enter website URL" v-model="formData.website" required>
      </div>
      <div class="form-group">
        <label for="productName">Product Name:</label>
        <input type="text" class="form-control" placeholder="Enter product name" v-model="formData.productName" required>
      </div>
      <div class="form-group">
        <label for="address">Address:</label>
        <input type="text" class="form-control" placeholder="Enter address" v-model="formData.address" required>
      </div>
      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" class="form-control" placeholder="Enter email address" v-model="formData.email" required>
      </div>
      <div class="form-group">
        <label for="phone">Phone:</label>
        <input type="tel" class="form-control" placeholder="Enter phone number" v-model="formData.phone" required>
      </div>

      <button type="submit" class="btn btn-primary">Generate QR Code</button>
    </form>

    <!-- Display QR Code -->
    <div class="qr-code-section" v-if="qrCodeImageUrl">
      <qrcode-vue :value="qrCodeImageUrl" :size="qrCodeSize" level="H" />
      <button @click="downloadQRCode" class="btn btn-primary mt-3">Download QR Code</button>
    </div>
  </div>
</template>

<script>
import QrcodeVue from 'qrcode.vue';
import QRCode from 'qrcode';

export default {
  data() {
    return {
      formData: {
        website: '',
        productName: '',
        address: '',
        email: '',
        phone: ''
      },
      qrCodeImageUrl: null,
      qrCodeSize: 200
    };
  },
  components: {
    QrcodeVue
  },
  methods: {
    generateQRCode() {
      const qrData = JSON.stringify(this.formData);

      QRCode.toDataURL(qrData, (error, url) => {
        if (error) {
          console.error("QR Code generation error:", error);
        } else {
          this.qrCodeImageUrl = url;
        }
      });
    },

    downloadQRCode() {
      if (this.qrCodeImageUrl) {
        const link = document.createElement('a');
        link.href = this.qrCodeImageUrl;
        link.download = 'qrcode.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }
    }
  }
};
</script>

<style scoped>
/* Add your component-specific styles here */
</style>
