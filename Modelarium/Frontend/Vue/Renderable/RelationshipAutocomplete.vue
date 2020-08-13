<template>
  <input
    v-model="query"
    type="text"
    name=""
    class="modelarium-autocomplete"
    autocomplete="off"
  >
    <option v-for="o in options" v-bind:key="o.id" :value="o.id">
      {{ o.name }}
    </option>
  </select>
</template>
<script>
import axios from "axios";

query($page: Int!) {
    posts(page: $page) {
        data {
            id
            title
        }

        paginatorInfo {
            currentPage
            perPage
            total
            lastPage
        }
    }
}


export default {
  data() {
    return {
      options: [],
    };
  },

  props: {
      "listQuery": {
          type: String,
          required: true
      }
  },

  methods: {
    fetch(page = 1) {
      return axios.post(
        '/graphql',
        {
            query: listQuery,
            variables: {
                page,
                query: 
            },
        }
      ).then((result) => {
        if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
        }
        const data = result.data.data;
        this.$set(this, 'list', data.posts.data);
        this.$set(this, 'pagination', data.posts.paginatorInfo);
      });
    },
  },
};
</script>
