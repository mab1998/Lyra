<template>
  <div class="text-right">
    <input type="text" v-model="field.value" :title="field.name" class="form-control" :id="field.column"
           :maxlength="(field.size && field.size.hardMode) ? field.size.number : false">
    <small v-if="field.size" class="font-weight-bold text-muted">
      <span :class="classLength">{{ length }}</span> / {{ field.size.number }}
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

        if ((this.field.size.number - this.length) > 10) {
          classLength = 'text-success';
        } else if ((this.field.size.number - this.length) <= 10 && (this.field.size.number - this.length) >= 0) {
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
