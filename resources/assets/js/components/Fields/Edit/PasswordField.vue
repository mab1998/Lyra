<template>
  <div class="text-right">
    <input type="password" v-model="field.value" :id="field.column" :title="field.name" class="form-control"
           :placeholder="field.placeholder">
    <small v-if="field.size" class="font-weight-bold text-muted">
      <span :class="classLength">{{ length }}</span> / {{ field.size }}
    </small>
  </div>
</template>

<script>
  export default {
    props: ['field', 'formData'],
    computed: {
      length: function () {
        return (this.field.value) ? this.field.value.length : 0;
      },
      classLength: function () {
        let classLength = '';

        if ((this.field.size - this.length) > 10) {
          classLength = 'text-success';
        } else if ((this.field.size - this.length) <= 10 && (this.field.size - this.length) >= 0) {
          classLength = 'text-warning';
        } else {
          classLength = 'text-danger';
        }

        return classLength;
      }
    }
  }
</script>

<style scoped>

</style>
