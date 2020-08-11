<template>
  <div class="modelarium-show">
    {|{ form }|}

    <div class="modelarium-show__buttons">
      {|{buttonEdit}|} {|{buttonDelete}|}
    </div>
  </div>
</template>

<script>
import axios from "axios";
import itemQuery from "raw-loader!./queryItem.graphql";
import mutationDelete from "raw-loader!./mutationDelete.graphql";
import model from "./model";

export default {
  data() {
    return {
      model: model,
      can: {
        edit: true,
        delete: true,
      },
    };
  },

  created() {
    this.get(this.$route.params.id);
  },

  methods: {
    get(id) {
      axios
        .post("/graphql", {
          query: itemQuery,
          variables: { id },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          const data = result.data.data;
          this.$set(this, "model", data.post);
        });
    },

    remove() {
      if (!window.confirm("Really delete?")) {
        return;
      }
      axios
        .post("/graphql", {
          query: mutationDelete,
          variables: { id: this.model.id },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          this.$router.push("/{|lowerName|}/");
        });
    },
  },
};
</script>
<style></style>
