<template>
  <div class="modelarium-autocomplete" :data-attribute="name">
    <select :name="name" :multiple="isMultiple" style="display: none">
      <option
        v-for="item in selectionVisible"
        :key="item.id"
        :value="item.id"
        selected="selected"
      ></option>
    </select>
    <div class="modelarium-autocomplete__container">
      <autocomplete
        :debounceTime="200"
        :search="autocompleteSearch"
        :get-result-value="autocompleteGetResultValue"
        :placeholder="placeholder"
        :aria-label="placeholder"
        @submit="onSubmit"
      ></autocomplete>
      <div>Total results: {{ paginatorInfo.total }}</div>
      <!--
      <input
        v-model="selectableQuery"
        type="text"
        :list="dataListId"
        :class="'modelarium-autocomplete__search ' + htmlClass"
        autocomplete="off"
        placeholder="search..."
      />
      <slot>
        <datalist :id="dataListId">
          <option
            v-for="item in selectable"
            :key="item.id"
            class="modelarium-autocomplete__item--selection"
            :value="item.id"
            :label="item[titleField]"
          />
        </datalist>
      </slot>
      -->
      <router-link
        :to="'/' + targetType + '/edit/'"
        target="_blank"
        title="Add a new value for this field"
        class="modelarium-autocomplete__button"
      >
        <span>＋ Add new</span>
      </router-link>
      <div class="modelarium-autocomplete__selection" v-if="isMultiple">
        <ul class="modelarium-autocomplete__list" tabindex="-1" title="">
          <li
            v-for="item in selectionVisible"
            :key="item.id"
            class="modelarium-autocomplete__item--selection"
            @click="removeItem(item)"
          >
            <slot v-bind:item="item">
              <span>{{ item[titleField] }}</span>
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
import Autocomplete from "@trevoreyre/autocomplete-vue/dist/autocomplete.esm";

export default {
  components: {
    Autocomplete,
  },

  data() {
    return {
      value: [],
      errorMessage: "",
      /**
       * Returned results
       */
      selectable: [],
      /**
       * What the user is typing
       */
      selectableQuery: "",
      /**
       * Pagination about returned results
       */
      paginatorInfo: {
        currentPage: 0,
        lastPage: 0,
        perPage: 0,
        total: 0,
      },
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

    /**
     *
     */
    isMultiple: {
      type: Boolean,
      default: false,
    },

    placeholder: {
      type: String,
      default: "search...",
    },
  },

  computed: {
    dataListId() {
      return "datalist-" + targetType;
    },

    selectionVisible() {
      if (!this.selectionQuery) {
        return this.value;
      }
      return this.value.filter(
        (i) => i[this.titleField].indexOf(this.selectionQuery) != -1
      );
    },
  },

  methods: {
    saveSelectionAndReset(e) {
      let val = e.target.value;
      if (val) {
        this.optionVal = val;
      }
      e.target.value = "";
    },

    onSubmit(result) {
      console.log(result);
      if (this.isMultiple) {
        this.value.push(result.id);
      } else {
        if (result) {
          this.$set(this, "value", result.id);
        } else {
          this.$set(this, "value", 0);
        }
      }
      this.$emit("input", this.value);
    },

    autocompleteSearch(input) {
      this.selectableQuery = input;
      return this.fetch();
    },

    autocompleteGetResultValue(result) {
      return result[this.titleField];
    },

    async fetch() {
      this.isLoading = true;
      return axios
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
          const results = data[this.targetTypePlural].data;
          const paginatorInfo = data[this.targetTypePlural].paginatorInfo;
          this.$set(this, "selectable", results);
          this.$set(this, "paginatorInfo", paginatorInfo);
          return results;
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
