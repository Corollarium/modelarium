<template>
  <div class="modelarium-autocomplete">
    <select :name="name" multiple="multiple" style="display: none">
      <option
        v-for="item in selectionVisible"
        :key="item.id"
        :value="item.id"
      ></option>
    </select>
    <div class="modelarium-autocomplete__header">
      <router-link
        :to="'/' + targetType + '/edit/'"
        target="_blank"
        title="Add a new value for this field"
        class="modelarium-autocomplete__button"
      >
        <span>＋ Add new</span>
      </router-link>
    </div>
    <div class="modelarium-autocomplete__container">
      <input
        v-model="selectableQuery"
        type="text"
        :class="'modelarium-autocomplete__search ' + htmlClass"
        autocomplete="off"
        placeholder="search..."
      />
      <div class="modelarium-autocomplete__selection">
        <ul class="modelarium-autocomplete__list" tabindex="-1" title="">
          <li
            v-for="item in selectionVisible"
            :key="item.id"
            class="modelarium-autocomplete__item--selection"
            @click="removeItem(item)"
          >
            <slot v-bind:item="item">
              <span>{{ item[fieldName] }}</span>
            </slot>
          </li>
        </ul>
        <button
          type="button"
          class="modelarium-autocomplete__all"
          @click="removeAll"
        >
          Remove all
        </button>
      </div>
    </div>
    <p class="modelarium-autocomplete__message modelarium__message">
      {{ errorMessage }}
    </p>
  </div>
</template>

<script>
export default {
  data() {
    return {
      value: [],
      errorMessage: "",
      selectable: [],
      selectableQuery: "",
    };
  },

  props: {
    /**
     * The form field name
     */
    name: {
      type: String,
    },
    /**
     * html classes applied on <select></select>
     */
    htmlClass: {
      type: String,
    },
    /**
     * The field in the relationship that is used as a title
     */
    titleField: {
      type: String,
    },
    /**
     * The target type, such as 'post'
     */
    targetType: {
      type: String,
    },
    /**
     * The target type plural, such as 'posts'
     */
    targetTypePlural: {
      type: String,
    },

    /**
     * The GraphQL query
     */
    query: {
      type: String,
    },

    /**
     * Is this field required?
     */
    required: {
      type: Boolean,
      default: false,
    },
  },

  computed: {
    selectionVisible() {
      if (!this.selectionQuery) {
        return this.value;
      }
      return this.value.filter(
        (i) => i[this.titleField].indexOf(this.selectionQuery) != -1
      );
    },
  },

  watch: {
    selectableQuery(newval) {
      // TODO: debounce, avoid multiple calls
      this.fetch();
    },
  },

  methods: {
    async fetch() {
      this.isLoading = true;
      axios
        .post("/graphql", {
          query: this.query,
          variables: {
            page: 1,
            // TODO: query: selectableQuery,
            ...this.queryVariables,
          },
        })
        .then((result) => {
          if (result.data.errors) {
            // TODO
            console.error(result.data.errors);
            return;
          }
          const data = result.data.data;
          this.$set(this, "selectable", data[this.targetTypePlural].data);
        })
        .finally(() => {
          this.isLoading = false;
        });
    },

    addItem(item) {
      this.value.push(item);
    },

    removeItem(item) {
      this.value = this.value.filter((value) => item.id != value.id);
    },

    removeAll(item) {
      this.$set(this, "value", []);
    },
  },
};
</script>

<style>
.modelarium-autocomplete__container {
}
</style>
